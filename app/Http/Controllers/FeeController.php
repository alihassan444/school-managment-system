<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\ClassFeeStructure;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceAdjustment;
use App\Models\FeeInvoiceItem;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\SiblingDiscountRule;
use App\Models\Student;
use App\Models\StudentFeeStructure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:paid,partial,unpaid,overdue'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $month = Carbon::parse(($validated['month'] ?? today()->format('Y-m')).'-01')->startOfMonth();
        $status = $validated['status'] ?? '';
        $search = trim($validated['search'] ?? '');
        $query = FeeInvoice::with(['student.schoolClass', 'student.section', 'student.family'])
            ->whereDate('billing_month', $month->toDateString())
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->whereHas('student', fn ($student) => $student
                ->where(fn ($student) => $student->where('name', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%")
                    ->orWhereHas('family', fn ($family) => $family->where('family_code', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhereHas('primaryGuardian', fn ($guardian) => $guardian->where('name', 'like', "%{$search}%"))))));

        $monthInvoices = FeeInvoice::whereDate('billing_month', $month->toDateString())->where('status', '!=', 'cancelled');

        return view('fees.index', [
            'month' => $month,
            'status' => $status,
            'search' => $search,
            'invoices' => $query->latest('id')->paginate(20)->withQueryString(),
            'students' => Student::active()->orderBy('name')->get(['id', 'name', 'admission_no', 'roll_no']),
            'summary' => [
                'invoiced' => (clone $monthInvoices)->sum('total'),
                'paid' => (clone $monthInvoices)->sum('paid'),
                'outstanding' => (clone $monthInvoices)->selectRaw('COALESCE(SUM(total - paid), 0) as balance')->value('balance'),
                'overdue' => (clone $monthInvoices)->where('status', 'overdue')->count(),
            ],
            'dueDay' => (int) SchoolSetting::value('invoice_due_day', 10),
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
        ]);
        $month = Carbon::parse($validated['month'].'-01')->startOfMonth();
        abort_if(Carbon::parse($validated['due_date'])->lt($month), 422, 'Due date cannot be before the billing month.');
        $session = AcademicSession::current()->first();
        $category = FeeCategory::where('code', 'monthly')->first();
        $discountRules = SiblingDiscountRule::where('is_active', true)->orderByDesc('priority')->orderBy('id')->get();
        $structures = StudentFeeStructure::whereDate('effective_from', '<=', $month->toDateString())
            ->orderByDesc('effective_from')->orderByDesc('id')->get()->unique('student_id')->keyBy('student_id');
        $classStructures = ClassFeeStructure::whereDate('effective_from', '<=', $month->toDateString())
            ->orderByDesc('effective_from')->orderByDesc('id')->get()->unique('school_class_id')->keyBy('school_class_id');
        $classDefaults = SchoolClass::pluck('monthly_fee', 'id');
        $created = 0;

        Student::active()->orderBy('id')->chunkById(100, function ($students) use ($request, $month, $validated, $session, $category, $discountRules, $structures, $classStructures, $classDefaults, &$created): void {
            foreach ($students as $student) {
                $monthlyFee = $structures->get($student->id)?->monthly_fee
                    ?? $classStructures->get($student->school_class_id)?->monthly_fee
                    ?? $classDefaults->get($student->school_class_id)
                    ?? $student->monthly_fee;
                if (FeeInvoice::toCents($monthlyFee) <= 0) {
                    continue;
                }
                [$siblingDiscount, $discountRuleId] = $this->siblingDiscount($student, FeeInvoice::toCents($monthlyFee), $category?->id, $discountRules);
                try {
                    DB::transaction(function () use ($request, $student, $monthlyFee, $month, $validated, $session, $category, $siblingDiscount, $discountRuleId, &$created): void {
                        if (FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $month->toDateString())->exists()) {
                            return;
                        }
                        $invoice = FeeInvoice::create([
                            'student_id' => $student->id, 'family_id' => $student->family_id,
                            'billing_month' => $month, 'academic_session_id' => $session?->id,
                            'invoice_number' => 'INV-'.$month->format('Ym').'-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                            'issued_on' => today(), 'sibling_discount_rule_id' => $discountRuleId,
                            'due_date' => $validated['due_date'], 'subtotal' => 0,
                            'discount' => FeeInvoice::fromCents($siblingDiscount), 'sibling_discount' => FeeInvoice::fromCents($siblingDiscount),
                            'total' => 0, 'paid' => 0, 'status' => 'unpaid',
                        ]);
                        $invoice->items()->create([
                            'fee_category_id' => $category?->id, 'description' => 'Monthly Fee — '.$month->format('F Y'),
                            'charge_date' => $month, 'billing_month' => $month, 'item_type' => 'charge', 'status' => 'active',
                            'amount' => $monthlyFee, 'created_by' => $request->user()?->id,
                        ]);
                        $invoice->recalculateTotals();
                        $created++;
                    });
                } catch (QueryException $exception) {
                    if (! $this->isDuplicate($exception)) {
                        throw $exception;
                    }
                }
            }
        });

        return redirect()->route('fees.index', ['month' => $month->format('Y-m')])
            ->with('success', "{$created} monthly invoice(s) generated; existing invoices were reused.");
    }

    public function storeInvoice(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'], 'month' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $month = Carbon::parse($validated['month'].'-01')->startOfMonth();
        $student = Student::findOrFail($validated['student_id']);
        $existing = FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $month->toDateString())->first();
        if ($existing) {
            return redirect()->route('fees.show', $existing)->with('success', 'The existing monthly invoice was opened; no duplicate was created.');
        }
        $monthlyFee = $this->feeForMonth($student, $month);
        if (FeeInvoice::toCents($monthlyFee) <= 0) {
            throw ValidationException::withMessages(['student_id' => 'This student has no positive fee structure for the selected month.']);
        }

        try {
            $invoice = DB::transaction(function () use ($request, $validated, $student, $month, $monthlyFee): FeeInvoice {
                $invoice = FeeInvoice::create([
                    'student_id' => $student->id, 'family_id' => $student->family_id,
                    'academic_session_id' => AcademicSession::current()->value('id'),
                    'invoice_number' => 'INV-'.$month->format('Ym').'-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                    'billing_month' => $month, 'issued_on' => today(), 'due_date' => $validated['due_date'],
                    'subtotal' => 0, 'discount' => 0, 'total' => 0, 'paid' => 0, 'status' => 'unpaid', 'notes' => $validated['notes'] ?? null,
                ]);
                $invoice->items()->create([
                    'fee_category_id' => FeeCategory::where('code', 'monthly')->value('id'),
                    'description' => 'Monthly Fee — '.$month->format('F Y'), 'charge_date' => $month,
                    'billing_month' => $month, 'item_type' => 'charge', 'status' => 'active',
                    'amount' => $monthlyFee, 'created_by' => $request->user()?->id,
                ]);
                $invoice->recalculateTotals();
                $this->audit($request, 'manual-invoice-created', $invoice, null, ['billing_month' => $month->toDateString()]);

                return $invoice;
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicate($exception)) {
                throw $exception;
            }
            $invoice = FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $month->toDateString())->firstOrFail();
        }

        return redirect()->route('fees.show', $invoice)->with('success', 'Missing monthly invoice created safely.');
    }

    public function show(FeeInvoice $invoice)
    {
        $invoice->load([
            'student.guardian', 'student.schoolClass', 'student.section', 'student.family',
            'items.category', 'items.creator', 'payments' => fn ($query) => $query->latest('payment_date')->latest('id'),
            'adjustments.creator', 'adjustments.item',
        ]);

        return view('fees.show', [
            'invoice' => $invoice,
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(),
            'feeStructures' => $invoice->student->feeStructures()->with('creator')->latest('effective_from')->get(),
            'applicableMonthlyFee' => $this->feeForMonth($invoice->student, $invoice->billing_month),
        ]);
    }

    public function updateInvoice(Request $request, FeeInvoice $invoice)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date', 'after_or_equal:'.$invoice->billing_month->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'], 'reason' => ['required', 'string', 'max:500'],
        ]);
        $old = $invoice->only(['due_date', 'notes']);
        $invoice->update(['due_date' => $validated['due_date'], 'notes' => $validated['notes'] ?? null]);
        $invoice->recalculateTotals();
        $this->audit($request, 'invoice-details-updated', $invoice, $old, $invoice->only(['due_date', 'notes']) + ['reason' => $validated['reason']]);

        return back()->with('success', 'Invoice details updated and audited.');
    }

    public function storeCharge(Request $request, FeeInvoice $invoice)
    {
        $validated = $request->validate($this->chargeRules() + ['reason' => ['nullable', 'string', 'max:500']]);
        $this->assertChargeMonth($invoice, $validated['charge_date']);
        DB::transaction(function () use ($request, $validated, $invoice): void {
            $locked = FeeInvoice::lockForUpdate()->findOrFail($invoice->id);
            $item = $locked->items()->create([
                'fee_category_id' => $validated['fee_category_id'], 'description' => $validated['description'],
                'charge_date' => $validated['charge_date'], 'billing_month' => $locked->billing_month,
                'item_type' => 'charge', 'status' => 'active', 'amount' => $validated['amount'], 'created_by' => $request->user()?->id,
            ]);
            FeeInvoiceAdjustment::create([
                'fee_invoice_id' => $locked->id, 'fee_invoice_item_id' => $item->id, 'adjustment_type' => 'charge_added',
                'updated_amount' => $validated['amount'], 'difference_amount' => $validated['amount'],
                'updated_values' => $item->only(['fee_category_id', 'description', 'charge_date', 'amount']),
                'reason' => $validated['reason'] ?? 'Additional charge added', 'created_by' => $request->user()?->id,
            ]);
            $locked->recalculateTotals();
            $this->audit($request, 'invoice-charge-added', $item, null, $item->only(['fee_category_id', 'description', 'charge_date', 'amount']));
        });

        return back()->with('success', 'Charge added to the existing invoice; no duplicate invoice was created.');
    }

    public function updateCharge(Request $request, FeeInvoice $invoice, FeeInvoiceItem $item)
    {
        abort_unless($item->fee_invoice_id === $invoice->id && $item->status === 'active', 404);
        $validated = $request->validate($this->chargeRules() + ['reason' => ['required', 'string', 'max:500']]);
        $this->assertChargeMonth($invoice, $validated['charge_date']);

        DB::transaction(function () use ($request, $validated, $invoice, $item): void {
            $locked = FeeInvoice::lockForUpdate()->findOrFail($invoice->id);
            $lockedItem = FeeInvoiceItem::lockForUpdate()->findOrFail($item->id);
            $old = $lockedItem->only(['fee_category_id', 'description', 'charge_date', 'billing_month', 'amount']);
            $candidateCents = $locked->activeItems()->get()->sum(fn ($line) => FeeInvoice::toCents($line->amount))
                - FeeInvoice::toCents($lockedItem->amount) + FeeInvoice::toCents($validated['amount'])
                - FeeInvoice::toCents($locked->discount);
            if ($candidateCents < FeeInvoice::toCents($locked->paid)) {
                throw ValidationException::withMessages(['amount' => 'This edit would reduce the invoice below payments already applied. Record a supported refund or credit workflow instead.']);
            }

            $replacement = $lockedItem;
            if ($locked->payments()->completed()->exists()) {
                $lockedItem->update(['status' => 'adjusted', 'reversed_by' => $request->user()?->id, 'reversed_at' => now(), 'reversal_reason' => $validated['reason']]);
                $replacement = $locked->items()->create([
                    'fee_category_id' => $validated['fee_category_id'], 'description' => $validated['description'],
                    'charge_date' => $validated['charge_date'], 'billing_month' => $locked->billing_month,
                    'item_type' => 'charge', 'status' => 'active', 'source_item_id' => $lockedItem->id,
                    'amount' => $validated['amount'], 'created_by' => $request->user()?->id,
                ]);
            } else {
                $lockedItem->update([
                    'fee_category_id' => $validated['fee_category_id'], 'description' => $validated['description'],
                    'charge_date' => $validated['charge_date'], 'amount' => $validated['amount'],
                ]);
            }
            $new = $replacement->fresh()->only(['fee_category_id', 'description', 'charge_date', 'billing_month', 'amount']);
            FeeInvoiceAdjustment::create([
                'fee_invoice_id' => $locked->id, 'fee_invoice_item_id' => $lockedItem->id,
                'adjustment_type' => $locked->payments()->completed()->exists() ? 'replacement' : 'edit',
                'original_amount' => $old['amount'], 'updated_amount' => $validated['amount'],
                'difference_amount' => FeeInvoice::fromCents(FeeInvoice::toCents($validated['amount']) - FeeInvoice::toCents($old['amount'])),
                'original_values' => $old, 'updated_values' => $new, 'reason' => $validated['reason'], 'created_by' => $request->user()?->id,
            ]);
            $locked->recalculateTotals();
            $this->audit($request, 'invoice-charge-updated', $lockedItem, $old, $new + ['reason' => $validated['reason']]);
        });

        return back()->with('success', 'Charge updated with a complete adjustment history.');
    }

    public function reverseCharge(Request $request, FeeInvoice $invoice, FeeInvoiceItem $item)
    {
        abort_unless($item->fee_invoice_id === $invoice->id && $item->status === 'active', 404);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $validated, $invoice, $item): void {
            $locked = FeeInvoice::lockForUpdate()->findOrFail($invoice->id);
            $lockedItem = FeeInvoiceItem::lockForUpdate()->findOrFail($item->id);
            $candidateCents = $locked->activeItems()->get()->sum(fn ($line) => FeeInvoice::toCents($line->amount))
                - FeeInvoice::toCents($lockedItem->amount) - FeeInvoice::toCents($locked->discount);
            if ($candidateCents < FeeInvoice::toCents($locked->paid)) {
                throw ValidationException::withMessages(['reason' => 'This reversal would reduce the invoice below payments already applied. Use a supported refund or credit workflow.']);
            }
            $lockedItem->update(['status' => 'reversed', 'reversed_by' => $request->user()?->id, 'reversed_at' => now(), 'reversal_reason' => $validated['reason']]);
            FeeInvoiceAdjustment::create([
                'fee_invoice_id' => $locked->id, 'fee_invoice_item_id' => $lockedItem->id, 'adjustment_type' => 'reversal',
                'original_amount' => $lockedItem->amount, 'updated_amount' => 0,
                'difference_amount' => FeeInvoice::fromCents(-FeeInvoice::toCents($lockedItem->amount)),
                'original_values' => $lockedItem->only(['fee_category_id', 'description', 'charge_date', 'amount']),
                'updated_values' => ['status' => 'reversed'], 'reason' => $validated['reason'], 'created_by' => $request->user()?->id,
            ]);
            $locked->recalculateTotals();
            $this->audit($request, 'invoice-charge-reversed', $lockedItem, ['status' => 'active'], ['status' => 'reversed', 'reason' => $validated['reason']]);
        });

        return back()->with('success', 'Charge reversed; the historical line remains available in the audit trail.');
    }

    public function storeFeeStructure(Request $request, Student $student)
    {
        $validated = $request->validate([
            'monthly_fee' => ['required', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'effective_from' => ['required', 'date_format:Y-m'], 'reason' => ['required', 'string', 'max:500'],
        ]);
        $effective = Carbon::parse($validated['effective_from'].'-01')->startOfMonth();
        $structure = StudentFeeStructure::updateOrCreate(
            ['student_id' => $student->id, 'effective_from' => $effective->toDateString()],
            ['monthly_fee' => $validated['monthly_fee'], 'reason' => $validated['reason'], 'created_by' => $request->user()?->id]
        );
        $this->audit($request, 'student-fee-structure-saved', $structure, null, $structure->only(['student_id', 'monthly_fee', 'effective_from', 'reason']));

        return back()->with('success', 'Future monthly fee structure saved. Existing invoices were not changed.');
    }

    public function storeClassFeeStructure(Request $request, SchoolClass $schoolClass)
    {
        $validated = $request->validate([
            'monthly_fee' => ['required', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'effective_from' => ['required', 'date_format:Y-m'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $effective = Carbon::parse($validated['effective_from'].'-01')->startOfMonth();
        $structure = ClassFeeStructure::updateOrCreate(
            ['school_class_id' => $schoolClass->id, 'effective_from' => $effective->toDateString()],
            ['monthly_fee' => $validated['monthly_fee'], 'reason' => $validated['reason'], 'created_by' => $request->user()?->id]
        );
        $this->audit($request, 'class-fee-structure-saved', $structure, null, $structure->only(['school_class_id', 'monthly_fee', 'effective_from', 'reason']));

        return back()->with('success', 'Future class fee saved. Existing invoices were not changed.');
    }

    public function settings()
    {
        return view('fees.settings', [
            'classes' => SchoolClass::with(['feeStructures' => fn ($query) => $query->latest('effective_from')->latest('id')])->orderBy('sort_order')->orderBy('name')->get(),
            'students' => Student::active()->orderBy('name')->get(),
            'categories' => FeeCategory::orderBy('name')->get(),
            'discountRules' => SiblingDiscountRule::orderByDesc('priority')->orderBy('name')->get(),
            'settings' => SchoolSetting::where('group', 'fees')->pluck('value', 'key'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'invoice_due_day' => ['required', 'integer', 'between:1,28'],
            'late_fee_enabled' => ['required', 'boolean'],
            'late_fee_type' => ['required', 'in:fixed,percentage'],
            'late_fee_value' => ['required', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'late_fee_grace_days' => ['required', 'integer', 'between:0,90'],
        ]);
        if ($validated['late_fee_type'] === 'percentage' && FeeInvoice::toCents($validated['late_fee_value']) > 10000) {
            throw ValidationException::withMessages(['late_fee_value' => 'A percentage late fee cannot exceed 100%.']);
        }
        foreach ($validated as $key => $value) {
            SchoolSetting::updateOrCreate(['key' => $key], ['group' => 'fees', 'value' => $value]);
        }
        $this->audit($request, 'fee-settings-updated', $request->user(), null, $validated);

        return back()->with('success', 'Fee defaults saved for future billing. Historical invoices were not changed.');
    }

    public function studentLedger(Request $request, Student $student)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $invoiceQuery = $student->feeInvoices()->with([
            'activeItems.category', 'adjustments.creator', 'adjustments.invoice',
            'payments' => fn ($query) => $query->completed()->orderBy('payment_date'),
        ])
            ->where('status', '!=', 'cancelled');
        $paymentQuery = $student->payments()->completed()->with(['invoice', 'familyPayment'])->orderByDesc('payment_date')->orderByDesc('id');
        $this->applyLedgerFilters($invoiceQuery, $filters, 'billing_month');
        $this->applyLedgerFilters($paymentQuery, $filters, 'payment_date');
        $invoices = $invoiceQuery->orderByDesc('billing_month')->get();
        $payments = $paymentQuery->get();
        $totalCents = $invoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->total));
        $paidCents = $invoices->sum(fn ($invoice) => FeeInvoice::toCents($invoice->paid));

        return view('fees.student-ledger', [
            'student' => $student->load(['family.primaryGuardian', 'schoolClass', 'section']),
            'invoices' => $invoices,
            'payments' => $payments,
            'adjustments' => $invoices->flatMap->adjustments->sortByDesc('created_at')->values(),
            'filters' => $filters,
            'totals' => [
                'invoiced' => FeeInvoice::fromCents($totalCents),
                'paid' => FeeInvoice::fromCents($paidCents),
                'outstanding' => FeeInvoice::fromCents(max($totalCents - $paidCents, 0)),
            ],
        ]);
    }

    public function pdf(FeeInvoice $invoice)
    {
        $invoice->load(['student.schoolClass', 'student.section', 'activeItems.category', 'payments']);

        return Pdf::loadView('fees.invoice-pdf', $this->invoiceDocumentData($invoice))->setPaper('a4')->download($invoice->invoice_number.'.pdf');
    }

    private function invoiceDocumentData(FeeInvoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'school' => ['name' => SchoolSetting::value('school_name', 'Al Suffaa School'), 'address' => SchoolSetting::value('school_address', 'Lahore, Punjab'), 'phone' => SchoolSetting::value('school_phone', '0300-6694291')],
        ];
    }

    private function chargeRules(): array
    {
        return [
            'fee_category_id' => ['required', 'exists:fee_categories,id'], 'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:99999999.99'], 'charge_date' => ['required', 'date'],
        ];
    }

    private function assertChargeMonth(FeeInvoice $invoice, string $chargeDate): void
    {
        if (! Carbon::parse($chargeDate)->isSameMonth($invoice->billing_month)) {
            throw ValidationException::withMessages(['charge_date' => 'The charge date must fall within the invoice billing month.']);
        }
    }

    private function feeForMonth(Student $student, Carbon $month): string
    {
        $studentFee = StudentFeeStructure::where('student_id', $student->id)->whereDate('effective_from', '<=', $month->toDateString())
            ->latest('effective_from')->latest('id')->value('monthly_fee');
        if ($studentFee !== null) {
            return (string) $studentFee;
        }
        $classFee = $student->school_class_id ? ClassFeeStructure::where('school_class_id', $student->school_class_id)
            ->whereDate('effective_from', '<=', $month->toDateString())->latest('effective_from')->latest('id')->value('monthly_fee') : null;

        return (string) ($classFee ?? $student->schoolClass?->monthly_fee ?? $student->monthly_fee);
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

    private function siblingDiscount(Student $student, int $amountCents, ?int $categoryId, $rules): array
    {
        if (! $student->family_id || ! $student->sibling_order) {
            return [0, null];
        }
        foreach ($rules as $rule) {
            if ($rule->fee_category_id && $rule->fee_category_id !== $categoryId) {
                continue;
            }
            if ($student->sibling_order < $rule->minimum_sibling_order || $rule->discount_type === 'manual') {
                continue;
            }
            $discount = $rule->discount_type === 'fixed'
                ? FeeInvoice::toCents($rule->discount_value)
                : intdiv($amountCents * FeeInvoice::toCents($rule->discount_value) + 5000, 10000);

            return [min(max($discount, 0), $amountCents), $rule->id];
        }

        return [0, null];
    }

    private function isDuplicate(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }

    private function audit(Request $request, string $action, $model, ?array $old, array $new): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id, 'action' => $action,
            'auditable_type' => $model::class, 'auditable_id' => $model->id,
            'old_values' => $old, 'new_values' => $new, 'ip_address' => $request->ip(),
        ]);
    }
}
