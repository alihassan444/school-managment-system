<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
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

        $students = $query->orderBy('name')->paginate(15);
        $classes = Student::distinct()->pluck('class')->sort();

        return view('students.index', compact('students', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'father_name' => 'required|string|max:255|min:2',
                'class' => 'required|string|max:255|min:1',
                'campus' => 'required|in:campus_1,campus_2',
                'contact' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:20|min:10',
                'address' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive'
            ]);

            $student = DB::transaction(function () use ($validated) {
                $student = new Student($validated);
                $student->save();
                return $student;
            });

            return redirect()->route('students.index')
                            ->with('success', 'Student created successfully!');
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
        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'father_name' => 'required|string|max:255|min:2',
                'class' => 'required|string|max:255|min:1',
                'campus' => 'required|in:campus_1,campus_2',
                'contact' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:20|min:10',
                'address' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive'
            ]);

            DB::transaction(function () use ($student, $validated) {
                $student->fill($validated);
                $student->save();
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
}
