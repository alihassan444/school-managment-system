<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Filter by class
        if ($request->has('class') && $request->class != '') {
            $query->where('class', $request->class);
        }

        // Filter by campus
        if ($request->has('campus') && $request->campus != '') {
            $query->where('campus', $request->campus);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('name')->paginate(15)->withQueryString();
        $classes = Student::distinct()->pluck('class')->sort();
        $studentStats = [
            'total' => Student::count(),
            'active' => Student::active()->count(),
            'campus_1' => Student::where('campus', 'campus_1')->count(),
            'campus_2' => Student::where('campus', 'campus_2')->count(),
        ];

        return view('students.index', compact('students', 'classes', 'studentStats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->merge(['family_mode' => $request->input('family_mode', 'none')]);
            $validated = $request->validate([
                'admission_no' => 'nullable|string|max:50|unique:students,admission_no',
                'roll_no' => 'nullable|string|max:50',
                'name' => 'required|string|max:255|min:2',
                'father_name' => 'required|string|max:255|min:2',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'admission_date' => 'nullable|date|before_or_equal:today',
                'class' => 'nullable|string|max:255|min:1|required_without:school_class_id',
                'campus' => 'required|in:campus_1,campus_2',
                'academic_session_id' => 'nullable|exists:academic_sessions,id',
                'school_class_id' => 'nullable|exists:school_classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'contact' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:20|min:10',
                'address' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive',
                'monthly_fee' => 'required|numeric|min:0|max:99999999.99',
                'family_mode' => 'required|in:new,existing,none',
                'family_id' => 'nullable|required_if:family_mode,existing|exists:families,id',
                'family_display_name' => 'nullable|required_if:family_mode,new|string|max:150',
                'family_notes' => 'nullable|string|max:1000',
                'guardian_name' => 'nullable|required_if:family_mode,new|required_with:guardian_phone|string|max:150',
                'guardian_relation' => 'nullable|in:father,mother,legal_guardian,grandparent,other',
                'guardian_phone' => 'nullable|required_if:family_mode,new|string|max:30',
                'guardian_alternate_phone' => 'nullable|string|max:30',
                'guardian_email' => 'nullable|email|max:150',
                'guardian_cnic' => 'nullable|string|max:30',
                'guardian_occupation' => 'nullable|string|max:100',
            ]);

            $student = DB::transaction(function () use ($request, $validated) {
                $family = null;
                $guardian = null;
                if ($validated['family_mode'] === 'existing') {
                    $family = Family::with('primaryGuardian')->lockForUpdate()->findOrFail($validated['family_id']);
                    $guardian = $family->primaryGuardian;
                } elseif ($validated['family_mode'] === 'new') {
                    $guardian = $this->createOrUpdateGuardian($validated);
                    $family = Family::create([
                        'display_name' => $validated['family_display_name'],
                        'primary_guardian_id' => $guardian?->id,
                        'phone' => $guardian?->phone,
                        'address' => $validated['address'] ?? null,
                        'notes' => $validated['family_notes'] ?? null,
                    ]);
                    if ($guardian) {
                        $family->guardians()->attach($guardian->id, [
                            'relationship_role' => $guardian->relation,
                            'is_primary' => true,
                            'authorized_fee_payer' => true,
                        ]);
                    }
                } else {
                    $guardian = $this->createOrUpdateGuardian($validated);
                }
                $studentData = collect($validated)->except([
                    'family_mode', 'family_id', 'family_display_name', 'family_notes',
                    'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_alternate_phone',
                    'guardian_email', 'guardian_cnic', 'guardian_occupation',
                ])->all();
                $studentData['guardian_id'] = $guardian?->id;
                $studentData['family_id'] = $family?->id;
                $studentData['sibling_order'] = $family ? ((int) $family->students()->max('sibling_order') + 1) : null;
                if (! empty($studentData['school_class_id'])) {
                    $studentData['class'] = SchoolClass::findOrFail($studentData['school_class_id'])->name;
                }
                $student = new Student($studentData);
                $student->save();
                if ($guardian) {
                    $student->guardians()->attach($guardian->id, [
                        'relationship_role' => $guardian->relation,
                        'is_primary' => true,
                    ]);
                }
                $this->syncEnrollment($student);
                if ($family) {
                    AuditLog::create([
                        'user_id' => $request->user()?->id,
                        'action' => 'family-student-admitted',
                        'auditable_type' => Student::class,
                        'auditable_id' => $student->id,
                        'new_values' => ['family_id' => $family->id, 'family_code' => $family->family_code],
                        'ip_address' => $request->ip(),
                    ]);
                }
                return $student;
            });

            return redirect()->route('students.show', $student)
                            ->with('success', 'Student admitted successfully'.($student->family ? " under {$student->family->family_code}." : '.'));
        } catch (\Exception $e) {
            Log::error('Error creating student: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error creating student: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $student->load([
            'payments' => fn ($query) => $query->completed()->latest('payment_date')->latest('id'),
            'guardian', 'academicSession', 'schoolClass', 'section', 'enrollments.academicSession',
            'enrollments.schoolClass', 'enrollments.section', 'feeInvoices' => fn ($query) => $query->latest('billing_month'),
            'family.primaryGuardian', 'family.students.schoolClass', 'guardians',
        ]);
        $paidThisMonth = (float) $student->payments()
            ->completed()
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount');
        $remainingThisMonth = max((float) $student->monthly_fee - $paidThisMonth, 0);

        return view('students.show', compact('student', 'paidThisMonth', 'remainingThisMonth'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        return view('students.edit', ['student' => $student->load('guardian')] + $this->formData());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        try {
            $validated = $request->validate([
                'admission_no' => 'nullable|string|max:50|unique:students,admission_no,'.$student->id,
                'roll_no' => 'nullable|string|max:50',
                'name' => 'required|string|max:255|min:2',
                'father_name' => 'required|string|max:255|min:2',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'admission_date' => 'nullable|date|before_or_equal:today',
                'class' => 'nullable|string|max:255|min:1|required_without:school_class_id',
                'campus' => 'required|in:campus_1,campus_2',
                'academic_session_id' => 'nullable|exists:academic_sessions,id',
                'school_class_id' => 'nullable|exists:school_classes,id',
                'section_id' => 'nullable|exists:sections,id',
                'contact' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:20|min:10',
                'address' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive',
                'monthly_fee' => 'required|numeric|min:0|max:99999999.99',
                'guardian_name' => 'nullable|required_with:guardian_phone|string|max:150',
                'guardian_relation' => 'nullable|string|max:50',
                'guardian_phone' => 'nullable|string|max:30',
                'guardian_alternate_phone' => 'nullable|string|max:30',
                'guardian_email' => 'nullable|email|max:150',
                'guardian_cnic' => 'nullable|string|max:30',
                'guardian_occupation' => 'nullable|string|max:100',
            ]);

            DB::transaction(function () use ($student, $validated) {
                $guardian = $this->createOrUpdateGuardian($validated, $student->guardian);
                $studentData = collect($validated)->except([
                    'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_alternate_phone',
                    'guardian_email', 'guardian_cnic', 'guardian_occupation',
                ])->all();
                $studentData['guardian_id'] = $guardian?->id;
                if (! empty($studentData['school_class_id'])) {
                    $studentData['class'] = SchoolClass::findOrFail($studentData['school_class_id'])->name;
                }
                $student->fill($studentData);
                $student->save();
                $this->syncEnrollment($student);
            });

            return redirect()->route('students.index')
                            ->with('success', 'Student updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating student: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating student: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        try {
            $studentName = $student->name;
            
            // Delete associated payments first (cascade should handle this, but let's be explicit)
            $student->payments()->delete();
            
            // Delete the student
            $student->delete();
            
            return redirect()->route('students.index')
                            ->with('success', "Student '{$studentName}' deleted successfully!");
        } catch (\Exception $e) {
            return redirect()->route('students.index')
                            ->with('error', 'Error deleting student: ' . $e->getMessage());
        }
    }

    public function promotionForm(Request $request)
    {
        $classId = $request->integer('class_id') ?: null;
        return view('students.promote', [
            'classId' => $classId,
            'students' => $classId ? Student::active()->where('school_class_id', $classId)->orderBy('roll_no')->orderBy('name')->get() : collect(),
        ] + $this->formData());
    }

    public function promote(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);
        $targetClass = SchoolClass::findOrFail($validated['school_class_id']);
        if (! empty($validated['section_id'])) {
            abort_unless(Section::whereKey($validated['section_id'])->where('school_class_id', $targetClass->id)->exists(), 422, 'The selected section does not belong to the target class.');
        }

        DB::transaction(function () use ($validated, $targetClass): void {
            Student::whereKey($validated['student_ids'])->get()->each(function (Student $student) use ($validated, $targetClass): void {
                $student->update([
                    'academic_session_id' => $validated['academic_session_id'],
                    'school_class_id' => $targetClass->id,
                    'section_id' => $validated['section_id'] ?? null,
                    'class' => $targetClass->name,
                    'monthly_fee' => $targetClass->monthly_fee ?: $student->monthly_fee,
                ]);
                $this->syncEnrollment($student);
            });
        });

        return redirect()->route('students.index')->with('success', count($validated['student_ids']).' student(s) promoted successfully.');
    }

    private function formData(): array
    {
        return [
            'academicSessions' => AcademicSession::orderByDesc('starts_on')->get(),
            'schoolClasses' => SchoolClass::with('sections')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'families' => Family::with(['primaryGuardian', 'students.schoolClass'])->orderBy('family_code')->get(),
        ];
    }

    private function createOrUpdateGuardian(array $data, ?Guardian $guardian = null): ?Guardian
    {
        if (empty($data['guardian_name']) && empty($data['guardian_phone'])) {
            return $guardian;
        }

        $guardian ??= new Guardian;
        $guardian->fill([
            'name' => $data['guardian_name'] ?: $data['father_name'],
            'relation' => $data['guardian_relation'] ?? 'father',
            'phone' => $data['guardian_phone'] ?: ($data['mobile'] ?? ''),
            'alternate_phone' => $data['guardian_alternate_phone'] ?? null,
            'email' => $data['guardian_email'] ?? null,
            'cnic' => $data['guardian_cnic'] ?? null,
            'occupation' => $data['guardian_occupation'] ?? null,
            'address' => $data['address'] ?? null,
        ])->save();

        return $guardian;
    }

    private function syncEnrollment(Student $student): void
    {
        if (! $student->academic_session_id || ! $student->school_class_id) {
            return;
        }

        Enrollment::updateOrCreate(
            ['student_id' => $student->id, 'academic_session_id' => $student->academic_session_id],
            [
                'school_class_id' => $student->school_class_id,
                'section_id' => $student->section_id,
                'roll_no' => $student->roll_no,
                'status' => 'enrolled',
                'enrolled_on' => $student->admission_date ?? today(),
            ]
        );
    }
}
