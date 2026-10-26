<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $classId = $request->integer('class_id') ?: SchoolClass::orderBy('sort_order')->value('id');
        return view('timetable.index', [
            'classId' => $classId,
            'classes' => SchoolClass::with('sections')->orderBy('sort_order')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Staff::where('status', 'active')->orderBy('name')->get(),
            'entries' => TimetableEntry::with(['schoolClass', 'section', 'subject', 'staff'])
                ->when($classId, fn ($query) => $query->where('school_class_id', $classId))
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:80'],
        ]);

        $overlap = TimetableEntry::where('day_of_week', $validated['day_of_week'])
            ->where('starts_at', '<', $validated['ends_at'])->where('ends_at', '>', $validated['starts_at'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($class) use ($validated) {
                    $class->where('school_class_id', $validated['school_class_id'])
                        ->where('section_id', $validated['section_id'] ?? null);
                });
                if (! empty($validated['staff_id'])) {
                    $query->orWhere('staff_id', $validated['staff_id']);
                }
            })->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['starts_at' => 'This class or teacher already has an overlapping period.']);
        }

        TimetableEntry::create($validated);
        return redirect()->route('timetable.index', ['class_id' => $validated['school_class_id']])->with('success', 'Timetable period added.');
    }

    public function destroy(TimetableEntry $entry)
    {
        $entry->delete();
        return back()->with('success', 'Timetable period removed.');
    }
}
