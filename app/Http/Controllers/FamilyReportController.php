<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FamilyReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.families', $this->data($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->data($request);
        return Pdf::loadView('reports.families-pdf', $data)->setPaper('a4', 'landscape')
            ->download('family-fee-report-'.$data['from']->format('Ymd').'-'.$data['to']->format('Ymd').'.pdf');
    }

    public function csv(Request $request)
    {
        $data = $this->data($request);
        return response()->streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Family ID', 'Guardian', 'Students', 'Billing-period fees', 'Applied to invoices', 'Current outstanding', 'Previous outstanding', 'Total outstanding', 'Cash received', 'Status']);
            foreach ($data['rows'] as $row) {
                fputcsv($output, [$row['family']->family_code, $row['family']->primaryGuardian?->name, $row['students'], $row['due'], $row['collected'], $row['outstanding'], $row['previous_outstanding'], $row['total_outstanding'], $row['cash_collected'], $row['status']]);
            }
            fclose($output);
        }, 'family-fee-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function data(Request $request): array
    {
        $from = Carbon::parse($request->input('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', today()->toDateString()))->endOfDay();
        $familyId = $request->integer('family_id') ?: null;
        $classId = $request->integer('class_id') ?: null;
        $status = $request->string('status')->toString();

        $families = Family::with(['primaryGuardian', 'students' => fn ($query) => $query
            ->when($classId, fn ($query) => $query->where('school_class_id', $classId))])
            ->when($familyId, fn ($query) => $query->whereKey($familyId))
            ->when($classId, fn ($query) => $query->whereHas('students', fn ($student) => $student->where('school_class_id', $classId)))
            ->orderBy('family_code')->get();

        $rows = $families->map(function (Family $family) use ($from, $to, $classId): array {
            $studentIds = $family->students->pluck('id');
            $invoiceQuery = FeeInvoice::whereIn('student_id', $studentIds)
                ->whereBetween('billing_month', [$from->copy()->startOfMonth()->toDateString(), $to->copy()->endOfMonth()->toDateString()])
                ->where('status', '!=', 'cancelled');
            $invoiceIds = (clone $invoiceQuery)->pluck('id');
            $billingPaymentQuery = Payment::whereIn('fee_invoice_id', $invoiceIds)->where('status', 'completed')
                ->whereDate('payment_date', '<=', $to->toDateString());
            $paymentQuery = Payment::whereIn('student_id', $studentIds)->where('status', 'completed')
                ->whereDate('payment_date', '>=', $from->toDateString())
                ->whereDate('payment_date', '<=', $to->toDateString());
            $due = (float) (clone $invoiceQuery)->sum('total');
            $collected = (float) (clone $billingPaymentQuery)->sum('amount');
            $cashCollected = (float) (clone $paymentQuery)->sum('amount');
            $outstanding = max($due - $collected, 0);
            $previousInvoices = FeeInvoice::whereIn('student_id', $studentIds)
                ->whereDate('billing_month', '<', $from->copy()->startOfMonth()->toDateString())
                ->where('status', '!=', 'cancelled');
            $previousDue = (float) (clone $previousInvoices)->sum('total');
            $previousPaid = (float) Payment::completed()->whereIn('fee_invoice_id', (clone $previousInvoices)->pluck('id'))
                ->whereDate('payment_date', '<=', $to->toDateString())->sum('amount');
            $previousOutstanding = max($previousDue - $previousPaid, 0);
            $totalOutstanding = $previousOutstanding + $outstanding;
            $status = $outstanding <= 0 ? 'paid' : ($collected > 0 ? 'partial' : 'unpaid');
            return compact('family', 'due', 'collected', 'outstanding', 'previousOutstanding', 'totalOutstanding', 'cashCollected', 'status') + [
                'students' => $studentIds->count(),
                'previous_outstanding' => $previousOutstanding,
                'total_outstanding' => $totalOutstanding,
                'cash_collected' => $cashCollected,
            ];
        })->when(in_array($status, ['paid', 'partial', 'unpaid'], true), fn ($rows) => $rows->where('status', $status))->values();

        return [
            'rows' => $rows, 'from' => $from, 'to' => $to, 'familyId' => $familyId,
            'classId' => $classId, 'status' => $status,
            'families' => Family::with('primaryGuardian')->orderBy('family_code')->get(),
            'classes' => SchoolClass::orderBy('sort_order')->get(),
            'totals' => [
                'families' => $rows->count(), 'students' => $rows->sum('students'),
                'due' => $rows->sum('due'), 'collected' => $rows->sum('collected'),
                'cash_collected' => $rows->sum('cash_collected'),
                'outstanding' => $rows->sum('outstanding'),
                'previous_outstanding' => $rows->sum('previous_outstanding'),
                'total_outstanding' => $rows->sum('total_outstanding'),
            ],
        ];
    }
}
