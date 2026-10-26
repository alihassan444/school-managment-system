<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FamilyPayment;
use App\Models\FamilyPaymentAllocation;
use App\Models\FamilyStatement;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\SchoolSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyFeeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $familyId = $request->integer('family_id') ?: null;
        $search = trim($request->string('search')->toString());
        $selectedMonth = Carbon::createFromFormat('Y-m', $validated['month'] ?? today()->format('Y-m'))->startOfMonth();
        $families = Family::with([
            'primaryGuardian',
            'students' => fn ($query) => $query->with(['schoolClass', 'feeInvoices' => fn ($invoice) => $invoice
                ->whereDate('billing_month', $selectedMonth->toDateString())->where('status', '!=', 'cancelled')])->orderBy('name'),
        ])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('family_code', 'like', "%{$search}%")
                ->orWhere('display_name', 'like', "%{$search}%")
                ->orWhereHas('primaryGuardian', fn ($guardian) => $guardian->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('guardians', fn ($guardian) => $guardian->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('students', fn ($student) => $student->where('name', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%")->orWhere('roll_no', 'like', "%{$search}%"))))
            ->orderBy('family_code')->limit(50)->get();
        $family = $familyId ? Family::with(['primaryGuardian', 'students.schoolClass'])->findOrFail($familyId) : null;
        $monthInvoices = $family ? $this->invoiceQueryForFamily($family)
            ->with(['student.schoolClass', 'items'])
            ->whereDate('billing_month', $selectedMonth->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date')->orderBy('id')->get() : collect();
        $invoices = $monthInvoices->filter(fn ($invoice) => FeeInvoice::toCents($invoice->paid) < FeeInvoice::toCents($invoice->total))->values();
        $currentFeesCents = $monthInvoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
        $currentPaidCents = $monthInvoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->paid));
        $currentOutstandingCents = max($currentFeesCents - $currentPaidCents, 0);
        $previousOutstandingCents = $family ? $this->invoiceQueryForFamily($family)
            ->whereDate('billing_month', '<', $selectedMonth->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get(['total', 'paid'])->sum(fn ($invoice) => max(FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid), 0)) : 0;
        $currentFees = FeeInvoice::fromCents($currentFeesCents);
        $currentPaid = FeeInvoice::fromCents($currentPaidCents);
        $currentOutstanding = FeeInvoice::fromCents($currentOutstandingCents);
        $previousOutstanding = FeeInvoice::fromCents($previousOutstandingCents);
        $totalOutstanding = FeeInvoice::fromCents($previousOutstandingCents + $currentOutstandingCents);
        $billingPayments = $family ? FamilyPayment::query()
            ->with('collector')
            ->where('family_id', $family->id)
            ->where('status', 'completed')
            ->whereHas('allocations.invoice', fn ($query) => $query->whereDate('billing_month', $selectedMonth->toDateString()))
            ->withSum(['allocations as selected_month_applied' => fn ($query) => $query
                ->whereHas('invoice', fn ($invoice) => $invoice->whereDate('billing_month', $selectedMonth->toDateString()))], 'amount')
            ->latest('payment_date')->latest('id')->get() : collect();
        $cashPayments = $family ? FamilyPayment::query()
            ->where('family_id', $family->id)
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$selectedMonth->toDateString(), $selectedMonth->copy()->endOfMonth()->toDateString()])
            ->latest('payment_date')->latest('id')->get() : collect();

        return view('family-fees.index', compact(
            'families', 'family', 'invoices', 'monthInvoices', 'search', 'selectedMonth',
            'currentFees', 'currentPaid', 'currentOutstanding', 'previousOutstanding',
            'totalOutstanding', 'billingPayments', 'cashPayments'
        ));
    }

    public function storeStatement(Request $request)
    {
        $validated = $request->validate([
            'family_id' => ['required', 'exists:families,id'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'invoice_ids' => ['nullable', 'array', 'min:1', 'required_without:billing_month'],
            'invoice_ids.*' => ['integer', 'exists:fee_invoices,id'],
        ]);

        $statement = DB::transaction(function () use ($request, $validated): FamilyStatement {
            $family = Family::lockForUpdate()->findOrFail($validated['family_id']);
            $requestedInvoices = FeeInvoice::with('student')->whereKey($validated['invoice_ids'] ?? [])->get();
            if (! empty($validated['invoice_ids']) && $requestedInvoices->count() !== count(array_unique($validated['invoice_ids']))) {
                throw ValidationException::withMessages(['invoice_ids' => 'One or more invoices could not be loaded.']);
            }
            if ($requestedInvoices->contains(fn ($invoice) => ($invoice->family_id ?? $invoice->student->family_id) !== $family->id)) {
                throw ValidationException::withMessages(['invoice_ids' => 'Every invoice must belong to the selected family.']);
            }

            $billingMonth = isset($validated['billing_month'])
                ? Carbon::createFromFormat('Y-m', $validated['billing_month'])->startOfMonth()
                : Carbon::parse($requestedInvoices->max('billing_month'))->startOfMonth();

            // A family statement is a read-only snapshot of every genuine unpaid invoice
            // through the selected month. No carry-forward charge is inserted in the ledger.
            $invoices = $this->invoiceQueryForFamily($family)
                ->with('student')
                ->whereDate('billing_month', '<=', $billingMonth->toDateString())
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->whereColumn('paid', '<', 'total')
                ->lockForUpdate()->get();
            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages(['billing_month' => 'This family has no outstanding invoices through the selected month.']);
            }

            $balances = $invoices->mapWithKeys(fn ($invoice) => [$invoice->id => max(FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid), 0)]);
            $dueDate = $invoices->max('due_date');
            $statement = FamilyStatement::create([
                'family_id' => $family->id,
                'billing_month' => $billingMonth,
                'issue_date' => today(),
                'due_date' => $dueDate ? Carbon::parse($dueDate) : today(),
                'total_amount' => FeeInvoice::fromCents($balances->sum()),
                'created_by' => $request->user()?->id,
            ]);
            foreach ($invoices as $invoice) {
                $statement->items()->create(['fee_invoice_id' => $invoice->id, 'balance_at_issue' => FeeInvoice::fromCents($balances[$invoice->id])]);
            }
            $this->audit($request, 'family-statement-created', $statement, ['invoice_ids' => $invoices->pluck('id')->all()]);

            return $statement;
        });

        return redirect()->route('family-fees.statements.show', $statement)->with('success', 'Combined family statement generated without duplicating charges.');
    }

    public function showStatement(FamilyStatement $statement)
    {
        return view('family-fees.statement', ['statement' => $statement] + $this->statementDocumentData($statement));
    }

    public function statementPdf(FamilyStatement $statement)
    {
        return Pdf::loadView('family-fees.statement-pdf', ['statement' => $statement] + $this->statementDocumentData($statement))
            ->download($statement->statement_number.'.pdf');
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'family_id' => ['required', 'exists:families,id'],
            'idempotency_key' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'payment_method' => ['required', 'in:cash,bank,card,cheque,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'payment_scope' => ['nullable', 'in:selected_month,all_outstanding'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($existing = FamilyPayment::where('idempotency_key', $validated['idempotency_key'])->first()) {
            return redirect()->route('family-fees.receipts.show', $existing)->with('success', 'This payment was already recorded; the original receipt is shown.');
        }

        $receivedCents = FeeInvoice::toCents($validated['amount']);
        $billingMonth = Carbon::createFromFormat('Y-m', $validated['billing_month'] ?? today()->format('Y-m'))->startOfMonth();
        $paymentScope = $validated['payment_scope'] ?? 'all_outstanding';

        try {
            $familyPayment = DB::transaction(function () use ($request, $validated, $receivedCents, $billingMonth, $paymentScope): FamilyPayment {
                $family = Family::lockForUpdate()->findOrFail($validated['family_id']);
                $invoices = $this->invoiceQueryForFamily($family)
                    ->with('student')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereColumn('paid', '<', 'total')
                    ->when(
                        $paymentScope === 'selected_month',
                        fn ($query) => $query->whereDate('billing_month', $billingMonth->toDateString()),
                        fn ($query) => $query->whereDate('billing_month', '<=', $billingMonth->toDateString())
                    )
                    ->orderBy('due_date')->orderBy('id')
                    ->lockForUpdate()->get();
                $outstandingCents = $invoices->sum(fn ($invoice) => max(FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid), 0));
                if ($outstandingCents <= 0) {
                    $message = $paymentScope === 'selected_month'
                        ? 'This family has no outstanding balance for '.$billingMonth->format('F Y').'.'
                        : 'This family has no outstanding balance through '.$billingMonth->format('F Y').'.';
                    throw ValidationException::withMessages(['amount' => $message]);
                }
                if ($receivedCents > $outstandingCents) {
                    throw ValidationException::withMessages(['amount' => 'The received amount cannot exceed the current family outstanding balance.']);
                }

                $remainingCents = $receivedCents;
                $allocationCents = collect();
                foreach ($invoices as $invoice) {
                    if ($remainingCents <= 0) {
                        break;
                    }
                    $balance = max(FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid), 0);
                    $cents = min($remainingCents, $balance);
                    if ($cents > 0) {
                        $allocationCents->put($invoice->id, $cents);
                    }
                    $remainingCents -= $cents;
                }

                $invoices = $invoices->keyBy('id');

                $familyPayment = FamilyPayment::create([
                    'family_id' => $family->id,
                    'billing_month' => $billingMonth,
                    'payment_scope' => $paymentScope,
                    'idempotency_key' => $validated['idempotency_key'],
                    'total_amount' => FeeInvoice::fromCents($receivedCents),
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'payment_date' => $validated['payment_date'],
                    'received_by' => $request->user()?->id,
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($allocationCents as $invoiceId => $cents) {
                    $invoice = $invoices[$invoiceId];
                    $amount = FeeInvoice::fromCents($cents);
                    $payment = Payment::create([
                        'student_id' => $invoice->student_id,
                        'fee_invoice_id' => $invoice->id,
                        'family_payment_id' => $familyPayment->id,
                        'recorded_by' => $request->user()?->id,
                        'amount' => $amount,
                        'payment_method' => $validated['payment_method'],
                        'reference_number' => $validated['reference_number'] ?? null,
                        'status' => 'completed',
                        'monthly_fee_amount' => $amount,
                        'uniform_amount' => 0, 'books_amount' => 0, 'paper_fund_amount' => 0,
                        'other_amount' => 0, 'previous_balance_amount' => 0, 'discount_amount' => 0,
                        'total_amount' => $amount, 'remaining_amount' => 0,
                        'description' => "Family payment {$familyPayment->receipt_number}",
                        'payment_date' => $validated['payment_date'],
                        'posted_at' => now(),
                        'receipt_number' => 'FRA-'.str_pad((string) $familyPayment->id, 6, '0', STR_PAD_LEFT).'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
                    ]);
                    FamilyPaymentAllocation::create([
                        'family_payment_id' => $familyPayment->id, 'payment_id' => $payment->id,
                        'student_id' => $invoice->student_id, 'fee_invoice_id' => $invoice->id, 'amount' => $amount,
                    ]);
                    $invoice->refreshPaymentStatus();
                }
                $this->audit($request, 'family-payment-recorded', $familyPayment, [
                    'billing_month' => $billingMonth->format('Y-m'),
                    'payment_scope' => $paymentScope,
                    'allocations' => $allocationCents->all(),
                ]);

                return $familyPayment;
            }, 3);
        } catch (QueryException $exception) {
            if ($existing = FamilyPayment::where('idempotency_key', $validated['idempotency_key'])->first()) {
                return redirect()->route('family-fees.receipts.show', $existing)->with('success', 'This payment was already recorded; the original receipt is shown.');
            }
            throw $exception;
        }

        return redirect()->route('family-fees.receipts.show', $familyPayment)->with('success', 'Family payment recorded successfully.');
    }

    public function showReceipt(FamilyPayment $familyPayment)
    {
        return view('family-fees.receipt', ['familyPayment' => $familyPayment] + $this->receiptDocumentData($familyPayment));
    }

    public function receiptPdf(FamilyPayment $familyPayment)
    {
        return Pdf::loadView('family-fees.receipt-pdf', ['familyPayment' => $familyPayment] + $this->receiptDocumentData($familyPayment))
            ->setPaper('a4')
            ->download($familyPayment->receipt_number.'.pdf');
    }

    public function feeStructure(Request $request, Family $family)
    {
        $data = $this->feeStructureData($request, $family);

        return view('family-fees.structure', $data);
    }

    public function feeStructurePdf(Request $request, Family $family)
    {
        $data = $this->feeStructureData($request, $family);

        return Pdf::loadView('family-fees.structure-pdf', $data)->setPaper('a4')
            ->download('family-fee-slip-'.$family->family_code.'-'.$data['month']->format('Y-m').'.pdf');
    }

    public function ledger(Request $request, Family $family)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $invoiceQuery = $this->invoiceQueryForFamily($family)
            ->with(['student.schoolClass', 'activeItems.category', 'payments' => fn ($query) => $query->completed()->orderBy('payment_date')])
            ->where('status', '!=', 'cancelled');
        $this->applyLedgerFilters($invoiceQuery, $filters, 'billing_month');
        $invoices = $invoiceQuery->orderByDesc('billing_month')->orderBy('student_id')->get();
        $rows = $invoices->groupBy(fn ($invoice) => $invoice->billing_month->format('Y-m'))->map(function ($monthInvoices, $month): array {
            $totalCents = $monthInvoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
            $paidCents = $monthInvoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->paid));

            return [
                'month' => Carbon::parse($month.'-01'),
                'invoices' => $monthInvoices,
                'total' => FeeInvoice::fromCents($totalCents),
                'paid' => FeeInvoice::fromCents($paidCents),
                'outstanding' => FeeInvoice::fromCents(max($totalCents - $paidCents, 0)),
            ];
        })->values();
        $familyPayments = $family->payments()->where('status', 'completed')->orderByDesc('payment_date')->orderByDesc('id');
        $standalonePayments = Payment::completed()->whereNull('family_payment_id')
            ->whereHas('invoice', fn ($query) => $query->where('family_id', $family->id)
                ->orWhereHas('student', fn ($student) => $student->where('family_id', $family->id)))
            ->with(['student', 'invoice'])->orderByDesc('payment_date')->orderByDesc('id');
        $this->applyLedgerFilters($familyPayments, $filters, 'payment_date');
        $this->applyLedgerFilters($standalonePayments, $filters, 'payment_date');
        $totalCents = $invoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
        $paidCents = $invoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->paid));

        return view('family-fees.ledger', [
            'family' => $family->load(['primaryGuardian', 'students.schoolClass']),
            'rows' => $rows,
            'familyPayments' => $familyPayments->get(),
            'standalonePayments' => $standalonePayments->get(),
            'filters' => $filters,
            'totals' => [
                'invoiced' => FeeInvoice::fromCents($totalCents),
                'paid' => FeeInvoice::fromCents($paidCents),
                'outstanding' => FeeInvoice::fromCents(max($totalCents - $paidCents, 0)),
            ],
        ]);
    }

    public function reverse(Request $request, FamilyPayment $familyPayment)
    {
        DB::transaction(function () use ($request, $familyPayment): void {
            $locked = FamilyPayment::with('allocations.invoice')->lockForUpdate()->findOrFail($familyPayment->id);
            if ($locked->status !== 'completed') {
                throw ValidationException::withMessages(['payment' => 'Only completed family payments can be reversed.']);
            }
            $locked->update(['status' => 'reversed', 'reversed_at' => now(), 'reversed_by' => $request->user()?->id]);
            Payment::where('family_payment_id', $locked->id)->update(['status' => 'reversed']);
            $locked->allocations->each(fn ($allocation) => $allocation->invoice->refreshPaymentStatus());
            $this->audit($request, 'family-payment-reversed', $locked, ['receipt_number' => $locked->receipt_number]);
        });

        return back()->with('success', 'Family payment reversed with a complete audit trail.');
    }

    private function statementDocumentData(FamilyStatement $statement): array
    {
        $statement->load([
            'family.primaryGuardian', 'family.students.schoolClass',
            'items.invoice.student.schoolClass',
        ]);
        $month = $statement->billing_month->copy()->startOfMonth();
        $currentMonthFeeCents = $this->invoiceQueryForFamily($statement->family)
            ->whereDate('billing_month', $month->toDateString())
            ->where('status', '!=', 'cancelled')->get(['total'])
            ->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
        $previousBalanceCents = $statement->items
            ->filter(fn ($item) => $item->invoice->billing_month->lt($month))
            ->sum(fn ($item) => FeeInvoice::toCents($item->balance_at_issue));
        $remainingBalanceCents = FeeInvoice::toCents($statement->total_amount);
        $totalPayableCents = $previousBalanceCents + $currentMonthFeeCents;

        return [
            'students' => $statement->family->students->sortBy('sibling_order')->values(),
            'school' => $this->schoolBrand(),
            'summary' => [
                'previous_balance' => FeeInvoice::fromCents($previousBalanceCents),
                'current_month_fee' => FeeInvoice::fromCents($currentMonthFeeCents),
                'total_payable' => FeeInvoice::fromCents($totalPayableCents),
                'amount_paid' => FeeInvoice::fromCents(max($totalPayableCents - $remainingBalanceCents, 0)),
                'remaining_balance' => FeeInvoice::fromCents($remainingBalanceCents),
            ],
        ];
    }

    private function receiptDocumentData(FamilyPayment $familyPayment): array
    {
        $familyPayment->load([
            'family.primaryGuardian', 'family.students.schoolClass', 'collector',
            'allocations.invoice',
        ]);
        $feeMonth = $familyPayment->allocations->max(fn ($allocation) => $allocation->invoice?->billing_month)
            ?? $familyPayment->payment_date;
        $feeMonth = Carbon::parse($feeMonth)->startOfMonth();
        $invoiceIds = $this->invoiceQueryForFamily($familyPayment->family)
            ->whereDate('billing_month', '<=', $feeMonth->toDateString())
            ->where('status', '!=', 'cancelled')->pluck('id');
        $chargesCents = FeeInvoice::whereKey($invoiceIds)->get(['total'])->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
        $lastAllocationPaymentId = (int) $familyPayment->allocations->max('payment_id');
        $paidThroughReceiptCents = Payment::completed()->whereIn('fee_invoice_id', $invoiceIds)
            ->when($lastAllocationPaymentId, fn ($query) => $query->where('id', '<=', $lastAllocationPaymentId))
            ->get(['amount'])->sum(fn ($payment) => FeeInvoice::toCents($payment->amount));
        $remainingBalanceCents = max($chargesCents - $paidThroughReceiptCents, 0);
        $paymentCents = FeeInvoice::toCents($familyPayment->total_amount);

        return [
            'students' => $familyPayment->family->students->sortBy('sibling_order')->values(),
            'school' => $this->schoolBrand(),
            'feeMonth' => $feeMonth,
            'summary' => [
                'total_payable' => FeeInvoice::fromCents($remainingBalanceCents + $paymentCents),
                'amount_paid' => FeeInvoice::fromCents($paymentCents),
                'remaining_balance' => FeeInvoice::fromCents($remainingBalanceCents),
            ],
        ];
    }

    private function schoolBrand(): array
    {
        return [
            'name' => SchoolSetting::value('school_name', 'Al Suffaa School'),
            'phone' => SchoolSetting::value('school_phone', '0300-6694291'),
            'email' => SchoolSetting::value('school_email'),
            'address' => SchoolSetting::value('school_address', 'Lahore, Punjab'),
            'currency' => SchoolSetting::value('currency', 'PKR') === 'PKR' ? 'Rs.' : SchoolSetting::value('currency', 'PKR'),
        ];
    }

    private function feeStructureData(Request $request, Family $family): array
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = Carbon::parse(($validated['month'] ?? today()->format('Y-m')).'-01')->startOfMonth();
        $family->load(['primaryGuardian', 'students.schoolClass', 'students.section']);
        $invoices = $this->invoiceQueryForFamily($family)
            ->with(['student.schoolClass', 'student.section', 'activeItems.category'])
            ->whereDate('billing_month', $month->toDateString())->where('status', '!=', 'cancelled')
            ->orderBy('student_id')->get()->keyBy('student_id');
        $rows = $family->students->sortBy('sibling_order')->map(function ($student) use ($invoices): array {
            $invoice = $invoices->get($student->id);

            return [
                'student' => $student, 'invoice' => $invoice,
                'items' => $invoice?->activeItems ?? collect(),
                'total' => $invoice?->total ?? '0.00', 'paid' => $invoice?->paid ?? '0.00',
                'outstanding' => FeeInvoice::fromCents(max(FeeInvoice::toCents($invoice?->total ?? 0) - FeeInvoice::toCents($invoice?->paid ?? 0), 0)),
            ];
        })->values();
        $previousOutstandingCents = $this->invoiceQueryForFamily($family)
            ->whereDate('billing_month', '<', $month->toDateString())->where('status', '!=', 'cancelled')
            ->get(['total', 'paid'])->sum(fn ($invoice) => max(FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid), 0));
        $currentFeeCents = $rows->sum(fn ($row) => FeeInvoice::toCents($row['total']));
        $currentPaidCents = $rows->sum(fn ($row) => FeeInvoice::toCents($row['paid']));
        $currentOutstandingCents = $rows->sum(fn ($row) => FeeInvoice::toCents($row['outstanding']));

        return [
            'family' => $family, 'month' => $month, 'rows' => $rows, 'school' => $this->schoolBrand(),
            'totals' => [
                'fee' => FeeInvoice::fromCents($currentFeeCents),
                'previous' => FeeInvoice::fromCents($previousOutstandingCents),
                'payable' => FeeInvoice::fromCents($currentFeeCents + $previousOutstandingCents),
                'paid' => FeeInvoice::fromCents($currentPaidCents),
                'outstanding' => FeeInvoice::fromCents($currentOutstandingCents + $previousOutstandingCents),
            ],
        ];
    }

    private function invoiceQueryForFamily(Family $family)
    {
        return FeeInvoice::query()->where(function ($query) use ($family): void {
            $query->where('family_id', $family->id)
                ->orWhere(function ($query) use ($family): void {
                    $query->whereNull('family_id')->whereHas('student', fn ($student) => $student->where('family_id', $family->id));
                });
        });
    }

    private function applyLedgerFilters($query, array $filters, string $column): void
    {
        if (! empty($filters['month'])) {
            $month = Carbon::parse($filters['month'].'-01');
            $query->whereYear($column, $month->year)->whereMonth($column, $month->month);
        } elseif (! empty($filters['year'])) {
            $query->whereYear($column, $filters['year']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    private function audit(Request $request, string $action, $model, array $new): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id, 'action' => $action,
            'auditable_type' => $model::class, 'auditable_id' => $model->id,
            'new_values' => $new, 'ip_address' => $request->ip(),
        ]);
    }
}
