<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\FeeInvoice;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $overdueInvoices = FeeInvoice::with('student')->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', today())->get();
        FeeInvoice::whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->update(['status' => 'overdue']);
        $months = collect(range(5, 0))->map(fn ($offset) => today()->subMonths($offset)->startOfMonth());
        $monthlyFinancial = $months->map(fn (Carbon $month) => [
            'label' => $month->format('M'),
            'due' => (float) FeeInvoice::whereDate('billing_month', $month)->sum('total'),
            'paid' => (float) Payment::completed()->whereYear('payment_date', $month->year)->whereMonth('payment_date', $month->month)->sum('amount'),
        ]);
        $attendanceFlow = collect(range(13, 0))->map(function ($offset) {
            $date = today()->subDays($offset);
            return [
                'label' => $date->format('d M'),
                'present' => AttendanceRecord::whereDate('attendance_date', $date)->where('status', 'present')->count(),
                'absent' => AttendanceRecord::whereDate('attendance_date', $date)->where('status', 'absent')->count(),
            ];
        });

        return view('home', [
            'totalStudents' => Student::count(),
            'activeStudents' => Student::active()->count(),
            'totalCollected' => Payment::completed()->sum('amount'),
            'collectedThisMonth' => Payment::completed()
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
            'recentStudents' => Student::latest()->take(5)->get(),
            'recentPayments' => Payment::with('student')->completed()
                ->latest('payment_date')
                ->latest('id')
                ->take(6)
                ->get(),
            'overdueCount' => $overdueInvoices->count(),
            'overdueAmount' => (float) $overdueInvoices->sum(fn ($invoice) => max((float) $invoice->total - (float) $invoice->paid, 0)),
            'todayPresent' => AttendanceRecord::whereDate('attendance_date', today())->where('status', 'present')->count(),
            'todayMarked' => AttendanceRecord::whereDate('attendance_date', today())->count(),
            'upcomingExams' => Exam::with('schoolClass')->whereDate('starts_on', '>=', today())->orderBy('starts_on')->take(5)->get(),
            'classDistribution' => Student::active()->selectRaw('class, COUNT(*) as total')->groupBy('class')->orderByDesc('total')->take(8)->get(),
            'monthlyFinancial' => $monthlyFinancial,
            'attendanceFlow' => $attendanceFlow,
        ]);
    }
}
