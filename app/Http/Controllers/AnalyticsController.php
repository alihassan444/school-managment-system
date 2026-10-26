<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\FeeInvoice;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $selectedDate = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date'] ?? now()->toDateString()
        )->startOfDay();

        $monthly = collect(range(5, 0))->map(function (int $monthsAgo) use ($selectedDate) {
            $month = $selectedDate->copy()->subMonths($monthsAgo);
            $monthEnd = $monthsAgo === 0
                ? $selectedDate->copy()->endOfDay()
                : $month->copy()->endOfMonth();

            return [
                'label' => $month->format('M'),
                'full_label' => $month->format('F Y'),
                'fees' => (float) Payment::completed()
                    ->whereDate('payment_date', '>=', $month->copy()->startOfMonth())
                    ->whereDate('payment_date', '<=', $monthEnd)
                    ->sum('amount'),
                'payments' => Payment::completed()
                    ->whereDate('payment_date', '>=', $month->copy()->startOfMonth())
                    ->whereDate('payment_date', '<=', $monthEnd)
                    ->count(),
                'students' => Student::query()
                    ->whereBetween('created_at', [
                        $month->copy()->startOfMonth(),
                        $monthEnd,
                    ])
                    ->count(),
            ];
        });

        $studentBase = Student::query()->whereDate('created_at', '<=', $selectedDate);

        $studentStatus = [
            'active' => (clone $studentBase)->active()->count(),
            'inactive' => (clone $studentBase)->where('status', 'inactive')->count(),
        ];

        $campuses = [
            'Campus 1' => (clone $studentBase)->where('campus', 'campus_1')->count(),
            'Campus 2' => (clone $studentBase)->where('campus', 'campus_2')->count(),
        ];

        $classes = (clone $studentBase)
            ->select('class', DB::raw('COUNT(*) as total'))
            ->groupBy('class')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $totalStudents = array_sum($studentStatus);
        $totalFees = (float) Payment::completed()->whereDate('payment_date', '<=', $selectedDate)->sum('amount');
        $paymentCount = Payment::completed()->whereDate('payment_date', '<=', $selectedDate)->count();
        $bestMonth = $monthly->sortByDesc('fees')->first();
        $activeStudentBase = (clone $studentBase)->active();
        $monthInvoices = FeeInvoice::whereDate('billing_month', $selectedDate->copy()->startOfMonth()->toDateString())
            ->where('status', '!=', 'cancelled');
        $hasMonthInvoices = (clone $monthInvoices)->exists();
        $expectedThisMonth = $hasMonthInvoices
            ? (float) (clone $monthInvoices)->sum('total')
            : (float) (clone $activeStudentBase)->sum('monthly_fee');
        $collectedThisMonth = $hasMonthInvoices
            ? (float) Payment::completed()->whereIn('fee_invoice_id', (clone $monthInvoices)->pluck('id'))
                ->whereDate('payment_date', '<=', $selectedDate)->sum('amount')
            : (float) Payment::completed()
                ->whereHas('student', fn ($query) => $query->active())
                ->whereDate('payment_date', '>=', $selectedDate->copy()->startOfMonth())
                ->whereDate('payment_date', '<=', $selectedDate)
                ->sum('amount');
        $remainingThisMonth = max($expectedThisMonth - $collectedThisMonth, 0);
        $overpaidThisMonth = max($collectedThisMonth - $expectedThisMonth, 0);
        $collectionRate = $expectedThisMonth > 0
            ? min(round(($collectedThisMonth / $expectedThisMonth) * 100), 100)
            : 0;
        $studentsWithoutFee = (clone $activeStudentBase)->where('monthly_fee', '<=', 0)->count();
        $dailyPayments = Payment::completed()
            ->with('student')
            ->whereDate('payment_date', $selectedDate)
            ->latest('id')
            ->get();
        $dailyStudents = Student::query()
            ->whereDate('created_at', $selectedDate)
            ->latest('created_at')
            ->get();

        return view('analytics.index', [
            'selectedDate' => $selectedDate,
            'isToday' => $selectedDate->isToday(),
            'monthly' => $monthly,
            'studentStatus' => $studentStatus,
            'campuses' => $campuses,
            'classes' => $classes,
            'totalStudents' => $totalStudents,
            'activeRate' => $totalStudents > 0 ? round(($studentStatus['active'] / $totalStudents) * 100) : 0,
            'totalFees' => $totalFees,
            'paymentCount' => $paymentCount,
            'averagePayment' => $paymentCount > 0 ? $totalFees / $paymentCount : 0,
            'bestMonth' => $bestMonth,
            'expectedThisMonth' => $expectedThisMonth,
            'collectedThisMonth' => $collectedThisMonth,
            'remainingThisMonth' => $remainingThisMonth,
            'overpaidThisMonth' => $overpaidThisMonth,
            'collectionRate' => $collectionRate,
            'studentsWithoutFee' => $studentsWithoutFee,
            'dailyPayments' => $dailyPayments,
            'dailyStudents' => $dailyStudents,
            'dailyFees' => (float) $dailyPayments->sum('amount'),
        ]);
    }
}
