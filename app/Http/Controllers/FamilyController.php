<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->string('search')->toString());
        $classId = $request->integer('class_id') ?: null;
        $families = Family::query()
            ->with('primaryGuardian')
            ->withCount(['students', 'students as active_students_count' => fn ($query) => $query->where('status', 'active')])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('family_code', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('guardians', fn ($guardian) => $guardian
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('students', fn ($student) => $student
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('father_name', 'like', "%{$search}%")
                            ->orWhere('roll_no', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%"));
                });
            })
            ->when($classId, fn ($query) => $query->whereHas('students', fn ($student) => $student->where('school_class_id', $classId)))
            ->latest()->paginate(15)->withQueryString();

        $families->getCollection()->each(function (Family $family): void {
            $family->setAttribute('monthly_fee_total', (float) $family->students()->active()->sum('monthly_fee'));
            $family->setAttribute('outstanding_total', $family->outstanding_balance);
        });

        return view('families.index', [
            'families' => $families,
            'search' => $search,
            'classId' => $classId,
            'classes' => SchoolClass::orderBy('sort_order')->get(),
            'unlinkedStudents' => Student::with(['guardian', 'schoolClass'])->whereNull('family_id')->orderBy('name')->limit(12)->get(),
            'unlinkedCount' => Student::whereNull('family_id')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_relation' => ['required', 'in:father,mother,legal_guardian,grandparent,other'],
            'guardian_phone' => ['required', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
        ]);

        $family = DB::transaction(function () use ($validated): Family {
            $guardian = Guardian::create([
                'name' => $validated['guardian_name'],
                'relation' => $validated['guardian_relation'],
                'phone' => $validated['guardian_phone'],
                'normalized_phone' => $this->normalizePhone($validated['guardian_phone']),
                'email' => $validated['guardian_email'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
            $family = Family::create([
                'display_name' => $validated['display_name'],
                'primary_guardian_id' => $guardian->id,
                'phone' => $validated['guardian_phone'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            $family->guardians()->attach($guardian->id, [
                'relationship_role' => $validated['guardian_relation'], 'is_primary' => true, 'authorized_fee_payer' => true,
            ]);
            $this->audit($request, 'family-created', $family, null, $family->toArray());
            return $family;
        });

        return redirect()->route('families.show', $family)->with('success', "{$family->family_code} created successfully.");
    }

    public function show(Request $request, Family $family)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $selectedMonth = Carbon::createFromFormat('Y-m', $validated['month'] ?? today()->format('Y-m'))->startOfMonth();
        $family->load([
            'primaryGuardian', 'guardians',
            'students' => fn ($query) => $query->with('schoolClass')->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('name'),
            'students.feeInvoices' => fn ($query) => $query->whereDate('billing_month', $selectedMonth->toDateString())->latest('billing_month'),
            'payments' => fn ($query) => $query->with(['collector', 'allocations.student', 'allocations.invoice'])
                ->whereHas('allocations.invoice', fn ($invoice) => $invoice->whereDate('billing_month', $selectedMonth->toDateString()))
                ->latest('payment_date')->latest('id'),
            'statements' => fn ($query) => $query->latest('issue_date')->latest('id'),
        ]);

        $studentIds = $family->students->pluck('id');
        $currentMonthInvoices = \App\Models\FeeInvoice::whereIn('student_id', $studentIds)
            ->whereDate('billing_month', $selectedMonth->toDateString())->get();
        $completedPayments = \App\Models\Payment::whereIn('student_id', $studentIds)->where('status', 'completed');
        $currentFees = (float) $currentMonthInvoices->sum('total');
        $currentPaid = (float) $currentMonthInvoices->sum('paid');
        $previousOutstanding = (float) \App\Models\FeeInvoice::whereIn('student_id', $studentIds)
            ->whereDate('billing_month', '<', $selectedMonth->toDateString())
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(total - paid), 0) balance')->value('balance');
        $metrics = [
            'active_students' => $family->students->where('status', 'active')->count(),
            'monthly_fees' => $currentFees,
            'outstanding' => max($currentFees - $currentPaid, 0),
            'previous_outstanding' => $previousOutstanding,
            'total_through_month' => $previousOutstanding + max($currentFees - $currentPaid, 0),
            'paid_this_month' => $currentPaid,
            'cash_received' => (float) (clone $completedPayments)->whereBetween('payment_date', [$selectedMonth->toDateString(), $selectedMonth->copy()->endOfMonth()->toDateString()])->sum('amount'),
            'overdue' => (float) \App\Models\FeeInvoice::whereIn('student_id', $studentIds)->where('due_date', '<', today())->whereIn('status', ['unpaid', 'partial', 'overdue'])->selectRaw('COALESCE(SUM(total - paid), 0) balance')->value('balance'),
            'last_payment' => (clone $completedPayments)->max('payment_date'),
        ];

        return view('families.show', compact('family', 'metrics', 'currentMonthInvoices', 'selectedMonth') + [
            'availableStudents' => Student::with(['schoolClass', 'family'])->where(fn ($query) => $query->whereNull('family_id')->orWhere('family_id', '!=', $family->id))->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, Family $family)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $old = $family->only(array_keys($validated));
        $family->update($validated);
        $this->audit($request, 'family-updated', $family, $old, $validated);
        return back()->with('success', 'Family details updated.');
    }

    public function linkStudent(Request $request, Family $family)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'confirm_reassignment' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $family, $validated): void {
            $student = Student::lockForUpdate()->findOrFail($validated['student_id']);
            if ($student->family_id === $family->id) {
                throw ValidationException::withMessages(['student_id' => 'This student is already linked to the family.']);
            }
            if ($student->family_id && ! ($validated['confirm_reassignment'] ?? false)) {
                throw ValidationException::withMessages(['confirm_reassignment' => 'Confirm the family reassignment before continuing.']);
            }
            $oldFamilyId = $student->family_id;
            $nextOrder = (int) $family->students()->max('sibling_order') + 1;
            $student->update(['family_id' => $family->id, 'sibling_order' => max($nextOrder, 1)]);
            if ($family->primary_guardian_id) {
                $student->guardians()->syncWithoutDetaching([$family->primary_guardian_id => [
                    'relationship_role' => $family->primaryGuardian?->relation ?? 'guardian', 'is_primary' => ! $student->guardian_id,
                ]]);
            }
            $this->audit($request, 'family-student-linked', $student, ['family_id' => $oldFamilyId], ['family_id' => $family->id]);
        });

        return back()->with('success', 'Student linked to the family without changing academic or fee history.');
    }

    public function unlinkStudent(Request $request, Family $family, Student $student)
    {
        abort_unless($student->family_id === $family->id, 404);
        DB::transaction(function () use ($request, $family, $student): void {
            $student->update(['family_id' => null, 'sibling_order' => null]);
            $this->audit($request, 'family-student-unlinked', $student, ['family_id' => $family->id], ['family_id' => null]);
        });
        return back()->with('success', 'Student unlinked. Historical invoices and payments were preserved.');
    }

    public function addGuardian(Request $request, Family $family)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'relation' => ['required', 'in:father,mother,legal_guardian,grandparent,other'],
            'phone' => ['required', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_primary' => ['nullable', 'boolean'],
            'authorized_fee_payer' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $family, $validated): void {
            $guardian = Guardian::create(Arr::except($validated, ['is_primary', 'authorized_fee_payer']) + [
                'normalized_phone' => $this->normalizePhone($validated['phone']),
            ]);
            if ($validated['is_primary'] ?? false) {
                DB::table('family_guardian')->where('family_id', $family->id)->update(['is_primary' => false, 'updated_at' => now()]);
                $family->update(['primary_guardian_id' => $guardian->id, 'phone' => $guardian->phone]);
            }
            $family->guardians()->attach($guardian->id, [
                'relationship_role' => $validated['relation'],
                'is_primary' => $validated['is_primary'] ?? false,
                'authorized_fee_payer' => $validated['authorized_fee_payer'] ?? false,
            ]);
            $this->audit($request, 'family-guardian-added', $guardian, null, ['family_id' => $family->id]);
        });
        return back()->with('success', 'Guardian added to the family.');
    }

    public function guardian(Guardian $guardian)
    {
        $guardian->load(['families.students.schoolClass', 'assignedStudents.schoolClass']);
        return view('families.guardian', compact('guardian'));
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '92')) $digits = '0'.substr($digits, 2);
        return $digits;
    }

    private function audit(Request $request, string $action, $model, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id, 'action' => $action,
            'auditable_type' => $model::class, 'auditable_id' => $model->id,
            'old_values' => $old, 'new_values' => $new, 'ip_address' => $request->ip(),
        ]);
    }
}
