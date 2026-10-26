<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicController extends Controller
{
    public function index()
    {
        return view('academics.index', [
            'classes' => SchoolClass::with(['sections.classTeacher', 'subjects'])->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => Staff::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function storeClass(Request $request)
    {
        SchoolClass::create($request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:school_classes,name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Class created successfully.');
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('sections')->where(fn ($query) => $query->where('school_class_id', $request->school_class_id)),
            ],
            'class_teacher_id' => ['nullable', 'exists:staff,id'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        Section::create($validated);

        return back()->with('success', 'Section added successfully.');
    }

    public function storeSubject(Request $request)
    {
        Subject::create($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', 'unique:subjects,code'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Subject created successfully.');
    }

    public function assignSubject(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:staff,id'],
            'max_marks' => ['required', 'integer', 'min:1', 'max:1000'],
            'pass_marks' => ['required', 'integer', 'min:0', 'lte:max_marks'],
        ]);

        SchoolClass::findOrFail($validated['school_class_id'])->subjects()->syncWithoutDetaching([
            $validated['subject_id'] => [
                'teacher_id' => $validated['teacher_id'] ?? null,
                'max_marks' => $validated['max_marks'],
                'pass_marks' => $validated['pass_marks'],
            ],
        ]);

        return back()->with('success', 'Subject assigned to class.');
    }

    public function destroyClass(SchoolClass $schoolClass)
    {
        abort_if($schoolClass->students()->exists(), 422, 'Move students before deleting this class.');
        $schoolClass->delete();
        return back()->with('success', 'Class deleted.');
    }

    public function destroySection(Section $section)
    {
        abort_if($section->students()->exists(), 422, 'Move students before deleting this section.');
        $section->delete();
        return back()->with('success', 'Section deleted.');
    }

    public function destroySubject(Subject $subject)
    {
        $subject->delete();
        return back()->with('success', 'Subject deleted.');
    }
}
