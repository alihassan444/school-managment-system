<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? $request->date('date')?->toDateString()
            : today()->toDateString();
        $classId = $request->integer('class_id') ?: null;
        $classes = SchoolClass::where('is_active', true)->orderBy('sort_order')->get();
        $students = collect();
        $records = collect();

        if ($classId) {
            $students = Student::active()->where('school_class_id', $classId)->with('section')->orderBy('roll_no')->orderBy('name')->get();
            $records = AttendanceRecord::whereDate('attendance_date', $date)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        }

        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'leave' => $records->where('status', 'leave')->count(),
            'unmarked' => max($students->count() - $records->count(), 0),
        ];

        return view('attendance.index', compact('date', 'classId', 'classes', 'students', 'records', 'summary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:present,absent,leave,late'],
        ]);

        $studentIds = Student::active()->where('school_class_id', $validated['class_id'])->pluck('id');

        DB::transaction(function () use ($validated, $studentIds): void {
            foreach ($validated['attendance'] as $studentId => $status) {
                if (! $studentIds->contains((int) $studentId)) {
                    continue;
                }
                AttendanceRecord::updateOrCreate(
                    ['student_id' => $studentId, 'attendance_date' => $validated['date']],
                    ['status' => $status, 'marked_by' => auth()->id()]
                );
            }
        });

        return redirect()->route('attendance.index', ['date' => $validated['date'], 'class_id' => $validated['class_id']])
            ->with('success', 'Attendance register saved.');
    }
}
