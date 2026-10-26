<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $classId = $request->integer('class_id') ?: null;
        $exams = Exam::with(['schoolClass', 'subjects.subject'])
            ->when($classId, fn ($query) => $query->where('school_class_id', $classId))
            ->latest('starts_on')->paginate(15)->withQueryString();

        return view('exams.index', [
            'exams' => $exams,
            'classes' => SchoolClass::where('is_active', true)->orderBy('sort_order')->get(),
            'classId' => $classId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', 'in:draft,published,completed'],
        ]);
        $validated['academic_session_id'] = AcademicSession::current()->value('id');
        $exam = Exam::create($validated);

        return redirect()->route('exams.show', $exam)->with('success', 'Exam created. Add its date sheet next.');
    }

    public function show(Exam $exam)
    {
        $exam->load(['schoolClass', 'subjects.subject']);
        $assignedIds = $exam->subjects->pluck('subject_id');
        $subjects = $exam->schoolClass->subjects()->whereNotIn('subjects.id', $assignedIds)->orderBy('name')->get();

        $students = Student::active()->where('school_class_id', $exam->school_class_id)->orderBy('roll_no')->orderBy('name')->get();
        return view('exams.show', compact('exam', 'subjects', 'students'));
    }

    public function storeSubject(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_date' => ['required', 'date', 'after_or_equal:'.$exam->starts_on->toDateString(), 'before_or_equal:'.$exam->ends_on->toDateString()],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
        ]);

        $classSubject = $exam->schoolClass->subjects()->whereKey($validated['subject_id'])->first();
        abort_unless($classSubject, 422, 'Assign this subject to the class first.');

        $validated['max_marks'] = (int) $classSubject->pivot->max_marks;
        $validated['pass_marks'] = (int) $classSubject->pivot->pass_marks;
        $exam->subjects()->updateOrCreate(['subject_id' => $validated['subject_id']], $validated);

        return back()->with('success', 'Date-sheet subject saved.');
    }

    public function marks(ExamSubject $examSubject)
    {
        $examSubject->load(['exam.schoolClass', 'subject']);
        $students = Student::active()->where('school_class_id', $examSubject->exam->school_class_id)
            ->orderBy('roll_no')->orderBy('name')->get();
        $marks = ExamMark::where('exam_subject_id', $examSubject->id)->get()->keyBy('student_id');

        return view('exams.marks', compact('examSubject', 'students', 'marks'));
    }

    public function storeMarks(Request $request, ExamSubject $examSubject)
    {
        $validated = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*.value' => ['nullable', 'numeric', 'min:0', 'max:'.$examSubject->max_marks],
            'marks.*.absent' => ['nullable', 'boolean'],
        ]);
        $studentIds = Student::active()->where('school_class_id', $examSubject->exam->school_class_id)->pluck('id');

        DB::transaction(function () use ($validated, $examSubject, $studentIds): void {
            foreach ($validated['marks'] as $studentId => $entry) {
                if (! $studentIds->contains((int) $studentId)) {
                    continue;
                }
                $absent = (bool) ($entry['absent'] ?? false);
                ExamMark::updateOrCreate(
                    ['exam_subject_id' => $examSubject->id, 'student_id' => $studentId],
                    ['marks_obtained' => $absent ? null : ($entry['value'] ?? null), 'is_absent' => $absent]
                );
            }
        });

        return back()->with('success', 'Marks saved.');
    }

    public function resultCard(Exam $exam, Student $student)
    {
        abort_unless($student->school_class_id === $exam->school_class_id, 404);
        $exam->load(['schoolClass', 'subjects.subject', 'subjects.marks' => fn ($query) => $query->where('student_id', $student->id)]);
        return view('exams.result-card', compact('exam', 'student'));
    }

    public function rollSlip(Exam $exam, Student $student)
    {
        abort_unless($student->school_class_id === $exam->school_class_id, 404);
        $exam->load(['schoolClass', 'subjects.subject']);
        return view('exams.roll-slip', compact('exam', 'student'));
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return redirect()->route('exams.index')->with('success', 'Exam deleted.');
    }
}
