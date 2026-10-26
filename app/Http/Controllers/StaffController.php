<?php

namespace App\Http\Controllers;

use App\Models\SalaryRecord;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->input('month', today()->format('Y-m')))->startOfMonth();
        return view('staff.index', [
            'month' => $month,
            'staffMembers' => Staff::with(['salaryRecords' => fn ($query) => $query->whereDate('salary_month', $month)])
                ->orderBy('name')->paginate(20)->withQueryString(),
            'salarySummary' => [
                'payable' => (float) SalaryRecord::whereDate('salary_month', $month)->sum('net_salary'),
                'paid' => (float) SalaryRecord::whereDate('salary_month', $month)->where('status', 'paid')->sum('net_salary'),
                'pending' => SalaryRecord::whereDate('salary_month', $month)->where('status', 'unpaid')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Staff::create($request->validate([
            'employee_no' => ['required', 'string', 'max:30', 'unique:staff,employee_no'],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'in:teacher,administrator,accountant,support'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', 'unique:staff,email'],
            'joined_on' => ['nullable', 'date', 'before_or_equal:today'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:500'],
        ]) + ['status' => 'active']);

        return back()->with('success', 'Staff member added.');
    }

    public function salary(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'salary_month' => ['required', 'date_format:Y-m'],
            'allowances' => ['required', 'numeric', 'min:0'],
            'deductions' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:unpaid,paid'],
            'paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $validated['salary_month'])->startOfMonth();
        $net = (float) $staff->basic_salary + (float) $validated['allowances'] - (float) $validated['deductions'];
        if ($net < 0) {
            throw ValidationException::withMessages(['deductions' => 'Deductions cannot exceed salary plus allowances.']);
        }
        if ($validated['status'] === 'paid' && empty($validated['paid_on'])) {
            throw ValidationException::withMessages(['paid_on' => 'Payment date is required for a paid salary.']);
        }

        SalaryRecord::updateOrCreate(
            ['staff_id' => $staff->id, 'salary_month' => $month->toDateString()],
            [
                'basic_salary' => $staff->basic_salary,
                'allowances' => $validated['allowances'],
                'deductions' => $validated['deductions'],
                'net_salary' => $net,
                'status' => $validated['status'],
                'paid_on' => $validated['paid_on'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
            ]
        );

        return redirect()->route('staff.index', ['month' => $month->format('Y-m')])->with('success', 'Salary record saved.');
    }

    public function payslip(SalaryRecord $salary)
    {
        $salary->load('staff');
        return view('staff.payslip', compact('salary'));
    }

    public function destroy(Staff $staff)
    {
        if ($staff->salaryRecords()->exists()) {
            $staff->update(['status' => 'inactive']);
            return back()->with('success', 'Staff member deactivated because payroll history exists.');
        }
        $staff->delete();
        return back()->with('success', 'Staff member deleted.');
    }
}
