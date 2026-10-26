<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with('student')->completed();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%");
            })->orWhere('receipt_number', 'like', "%{$search}%");
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $totalCollected = (clone $query)->sum('amount');
        $payments = $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('payments.index', compact('payments', 'totalCollected'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $student = null;
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = FeeInvoice::with('student')->findOrFail($request->invoice_id);
            $student = $invoice->student;
        }
        if ($request->has('student_id')) {
            $student = Student::findOrFail($request->student_id);
            $invoice ??= $student->feeInvoices()->whereNotIn('status', ['paid', 'cancelled'])
                ->whereColumn('paid', '<', 'total')->orderBy('due_date')->orderBy('id')->first();
        }

        $students = Student::active()->orderBy('name')->get();
        $outstandingInvoices = FeeInvoice::with(['student.schoolClass'])
            ->whereNotIn('status', ['paid', 'cancelled'])->whereColumn('paid', '<', 'total')
            ->orderBy('due_date')->orderBy('id')->get();

        return view('payments.create', compact('student', 'students', 'invoice', 'outstandingInvoices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_invoice_id' => 'nullable|exists:fee_invoices,id',
            'idempotency_key' => 'nullable|uuid',
            'monthly_fee_amount' => 'required|numeric|min:0|max:99999999.99',
            'uniform_amount' => 'required|numeric|min:0|max:99999999.99',
            'books_amount' => 'required|numeric|min:0|max:99999999.99',
            'paper_fund_amount' => 'required|numeric|min:0|max:99999999.99',
            'other_amount' => 'required|numeric|min:0|max:99999999.99',
            'previous_balance_amount' => 'required|numeric|min:0|max:99999999.99',
            'discount_amount' => 'required|numeric|min:0|max:99999999.99',
            'amount' => 'required|numeric|gt:0|max:99999999.99',
            'payment_method' => 'nullable|in:cash,bank,card,cheque,other',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'payment_date' => 'required|date|before_or_equal:today',
        ]);

        $subtotalCents = collect([
            $validated['monthly_fee_amount'],
            $validated['uniform_amount'],
            $validated['books_amount'],
            $validated['paper_fund_amount'],
            $validated['other_amount'],
            $validated['previous_balance_amount'],
        ])->sum(fn ($amount) => FeeInvoice::toCents($amount));

        if ($subtotalCents <= 0) {
            throw ValidationException::withMessages([
                'monthly_fee_amount' => 'Enter at least one fee charge before creating the receipt.',
            ]);
        }

        $discountCents = FeeInvoice::toCents($validated['discount_amount']);
        if ($discountCents > $subtotalCents) {
            throw ValidationException::withMessages([
                'discount_amount' => 'The discount cannot be greater than the total charges.',
            ]);
        }

        $totalCents = $subtotalCents - $discountCents;
        $amountCents = FeeInvoice::toCents($validated['amount']);

        if ($amountCents > $totalCents) {
            throw ValidationException::withMessages([
                'amount' => 'The paid amount cannot be greater than the final total.',
            ]);
        }

        $total = FeeInvoice::fromCents($totalCents);
        $remaining = FeeInvoice::fromCents($totalCents - $amountCents);

        if (! empty($validated['idempotency_key'])) {
            $existing = Payment::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existing) {
                return redirect()->route('payments.show', $existing)
                    ->with('success', "This payment was already recorded. Receipt: {$existing->receipt_number}");
            }
        }

        try {
            $payment = DB::transaction(function () use ($request, $validated, $total, $remaining) {
                $invoice = null;
                if (! empty($validated['fee_invoice_id'])) {
                    $invoice = FeeInvoice::lockForUpdate()->findOrFail($validated['fee_invoice_id']);
                    $invoice->recalculateTotals();
                    if ($invoice->student_id !== (int) $validated['student_id']) {
                        throw ValidationException::withMessages(['student_id' => 'The selected invoice belongs to another student.']);
                    }
                    $invoiceBalance = FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid);
                    if (FeeInvoice::toCents($validated['amount']) > $invoiceBalance) {
                        throw ValidationException::withMessages(['amount' => 'The payment cannot exceed the invoice balance.']);
                    }
                }

                $payment = Payment::create([
                    'student_id' => $validated['student_id'],
                    'fee_invoice_id' => $validated['fee_invoice_id'] ?? null,
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                    'recorded_by' => $request->user()?->id,
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'reference_number' => $validated['reference_number'] ?? null,
                    'status' => 'completed',
                    'monthly_fee_amount' => $validated['monthly_fee_amount'],
                    'uniform_amount' => $validated['uniform_amount'],
                    'books_amount' => $validated['books_amount'],
                    'paper_fund_amount' => $validated['paper_fund_amount'],
                    'other_amount' => $validated['other_amount'],
                    'previous_balance_amount' => $validated['previous_balance_amount'],
                    'discount_amount' => $validated['discount_amount'],
                    'total_amount' => $total,
                    'remaining_amount' => $remaining,
                    'description' => $validated['description'] ?? null,
                    'payment_date' => $validated['payment_date'],
                    'posted_at' => now(),
                    'receipt_number' => Payment::generateReceiptNumber(),
                ]);
                $invoice?->recalculateTotals();

                return $payment;
            }, 3);
        } catch (QueryException $exception) {
            if (! empty($validated['idempotency_key']) && $existing = Payment::where('idempotency_key', $validated['idempotency_key'])->first()) {
                return redirect()->route('payments.show', $existing)
                    ->with('success', "This payment was already recorded. Receipt: {$existing->receipt_number}");
            }
            throw $exception;
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', "Payment recorded successfully! Receipt: {$payment->receipt_number}");
    }

    /**
     * Display the specified resource (Receipt).
     */
    public function show(Payment $payment)
    {
        return view('payments.receipt', compact('payment'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        if ($payment->family_payment_id) {
            throw ValidationException::withMessages(['payment' => 'Family allocations cannot be reversed individually. Reverse the combined family payment instead.']);
        }

        DB::transaction(function () use ($payment): void {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($locked->status !== 'completed') {
                return;
            }
            $locked->update(['status' => 'reversed']);
            AuditLog::create([
                'user_id' => request()->user()?->id,
                'action' => 'individual-payment-reversed',
                'auditable_type' => Payment::class,
                'auditable_id' => $locked->id,
                'old_values' => ['status' => 'completed'],
                'new_values' => ['status' => 'reversed'],
                'ip_address' => request()->ip(),
            ]);
            $locked->invoice?->recalculateTotals();
        });

        return redirect()->route('payments.index')
            ->with('success', "Payment (Receipt: {$payment->receipt_number}) reversed. Its audit record was preserved.");
    }
    // Export and advanced filter helpers removed in simplified billing system
}
