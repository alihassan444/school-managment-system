<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceAdjustment;
use App\Models\FeeInvoiceItem;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentFeeStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdvancedFeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_generation_and_manual_generation_are_idempotent(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $month = today()->addMonth()->startOfMonth();
        $payload = ['month' => $month->format('Y-m'), 'due_date' => $month->copy()->day(10)->toDateString()];

        $this->actingAs($admin)->post(route('fees.generate'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('fees.generate'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('fees.invoices.store'), $payload + ['student_id' => $student->id])->assertRedirect();

        $invoice = FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $month)->firstOrFail();
        $this->assertDatabaseCount('fee_invoices', 1);
        $this->assertSame(1, $invoice->items()->active()->count());
        $this->assertSame('5000.00', $invoice->fresh()->total);
    }

    public function test_additional_charge_updates_only_the_selected_month(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $november = Carbon::create(2026, 11, 1);
        $december = Carbon::create(2026, 12, 1);
        $novemberInvoice = $this->invoiceWithLine($student, $november, 5000);
        $decemberInvoice = $this->invoiceWithLine($student, $december, 5000);
        $exam = FeeCategory::where('code', 'examination')->firstOrFail();

        $this->actingAs($admin)->post(route('fees.charges.store', $novemberInvoice), [
            'fee_category_id' => $exam->id,
            'description' => 'November examination fee',
            'amount' => '750.25',
            'charge_date' => '2026-11-15',
            'reason' => 'Mid-term assessment',
        ])->assertRedirect();

        $this->assertSame('5750.25', $novemberInvoice->fresh()->total);
        $this->assertSame('5000.00', $decemberInvoice->fresh()->total);
        $this->assertDatabaseHas('fee_invoice_adjustments', [
            'fee_invoice_id' => $novemberInvoice->id,
            'adjustment_type' => 'charge_added',
            'difference_amount' => 750.25,
        ]);
    }

    public function test_partial_payment_then_new_charge_preserves_payment_and_recalculates_balance(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $month = today()->startOfMonth();
        $invoice = $this->invoiceWithLine($student, $month, 5000);
        $this->payment($student, $invoice, 2000);
        $invoice->recalculateTotals();

        $this->actingAs($admin)->post(route('fees.charges.store', $invoice), [
            'fee_category_id' => FeeCategory::where('code', 'sports-fund')->value('id'),
            'description' => 'Sports fund',
            'amount' => 500,
            'charge_date' => $month->copy()->day(15)->toDateString(),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('5500.00', $invoice->total);
        $this->assertSame('2000.00', $invoice->paid);
        $this->assertSame('overdue', $invoice->status);
        $this->assertSame(350000, FeeInvoice::toCents($invoice->total) - FeeInvoice::toCents($invoice->paid));
    }

    public function test_unpaid_charge_edits_in_place_but_paid_charge_uses_a_replacement(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $month = today()->startOfMonth();
        $invoice = $this->invoiceWithLine($student, $month, 5000);
        $line = $invoice->items()->firstOrFail();
        $category = FeeCategory::where('code', 'monthly')->firstOrFail();

        $this->actingAs($admin)->put(route('fees.charges.update', [$invoice, $line]), [
            'fee_category_id' => $category->id,
            'description' => 'Corrected monthly tuition',
            'amount' => 5200,
            'charge_date' => $month->toDateString(),
            'reason' => 'Correct tuition amount',
        ])->assertRedirect();
        $this->assertSame($line->id, $invoice->items()->active()->firstOrFail()->id);
        $this->assertSame('5200.00', $invoice->fresh()->total);

        $this->payment($student, $invoice, 1000);
        $invoice->recalculateTotals();
        $this->actingAs($admin)->put(route('fees.charges.update', [$invoice, $line]), [
            'fee_category_id' => $category->id,
            'description' => 'Audited monthly tuition',
            'amount' => 5300,
            'charge_date' => $month->toDateString(),
            'reason' => 'Approved correction after receipt',
        ])->assertRedirect();

        $this->assertSame('adjusted', $line->fresh()->status);
        $replacement = $invoice->items()->active()->firstOrFail();
        $this->assertSame($line->id, $replacement->source_item_id);
        $this->assertSame('5300.00', $invoice->fresh()->total);
        $this->assertSame('1000.00', $invoice->fresh()->paid);
        $this->assertTrue(FeeInvoiceAdjustment::where('adjustment_type', 'replacement')->exists());
        $this->actingAs($admin)->get(route('fees.students.ledger', $student))->assertOk()
            ->assertSee('Adjustment history')->assertSee('Approved correction after receipt');
    }

    public function test_charge_reversal_keeps_the_original_line_and_audit_history(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $invoice = $this->invoiceWithLine($student, today()->startOfMonth(), 5000);
        $extra = $invoice->items()->create([
            'fee_category_id' => FeeCategory::where('code', 'event')->value('id'),
            'description' => 'School event', 'charge_date' => today(), 'billing_month' => today()->startOfMonth(),
            'item_type' => 'charge', 'status' => 'active', 'amount' => 300, 'created_by' => $admin->id,
        ]);
        $invoice->recalculateTotals();

        $this->actingAs($admin)->post(route('fees.charges.reverse', [$invoice, $extra]), [
            'reason' => 'Event cancelled',
        ])->assertRedirect();

        $this->assertDatabaseHas('fee_invoice_items', ['id' => $extra->id, 'status' => 'reversed']);
        $this->assertDatabaseHas('fee_invoice_adjustments', ['fee_invoice_item_id' => $extra->id, 'adjustment_type' => 'reversal']);
        $this->assertSame('5000.00', $invoice->fresh()->total);
    }

    public function test_effective_dated_master_fee_changes_only_future_invoices(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $november = Carbon::create(2026, 11, 1);
        $december = Carbon::create(2026, 12, 1);
        $novemberInvoice = $this->invoiceWithLine($student, $november, 5000);

        $this->actingAs($admin)->post(route('fees.structures.store', $student), [
            'monthly_fee' => 6000,
            'effective_from' => '2026-12',
            'reason' => 'Approved annual revision',
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('fees.invoices.store'), [
            'student_id' => $student->id,
            'month' => '2026-12',
            'due_date' => '2026-12-10',
        ])->assertRedirect();

        $this->assertSame('5000.00', $novemberInvoice->fresh()->total);
        $this->assertSame('6000.00', FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $december)->firstOrFail()->total);
        $this->assertTrue(StudentFeeStructure::where('student_id', $student->id)->where('monthly_fee', 6000)->exists());
    }

    public function test_family_search_expansion_and_printable_structure_show_all_children(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $ali = $this->student(['name' => 'Ali Ahmed', 'family_id' => $family->id, 'sibling_order' => 1]);
        $sara = $this->student(['name' => 'Sara Ahmed', 'mobile' => '03007770002', 'family_id' => $family->id, 'sibling_order' => 2]);
        $this->invoiceWithLine($ali, today()->startOfMonth(), 5000);
        $this->invoiceWithLine($sara, today()->startOfMonth(), 4500);

        $this->actingAs($admin)->get(route('family-fees.index', ['search' => 'Muhammad Ahmed', 'month' => today()->format('Y-m')]))
            ->assertOk()->assertSee($family->family_code)->assertSee('Ali Ahmed')->assertSee('Sara Ahmed');
        $this->actingAs($admin)->get(route('family-fees.structure', [$family, 'month' => today()->format('Y-m')]))
            ->assertOk()->assertSee('Family Fee Slip')->assertSee('Ali Ahmed')->assertSee('Sara Ahmed')->assertSee('9,500')
            ->assertDontSee('Monthly tuition')->assertDontSee('5,000.00');
        $this->actingAs($admin)->get(route('family-fees.structure.pdf', [$family, 'month' => today()->format('Y-m')]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_payment_submission_is_idempotent_and_reversal_never_deletes_receipt(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $invoice = $this->invoiceWithLine($student, today()->startOfMonth(), 5000);
        $key = (string) Str::uuid();
        $payload = [
            'student_id' => $student->id, 'fee_invoice_id' => $invoice->id, 'idempotency_key' => $key,
            'monthly_fee_amount' => 5000, 'uniform_amount' => 0, 'books_amount' => 0,
            'paper_fund_amount' => 0, 'other_amount' => 0, 'previous_balance_amount' => 0,
            'discount_amount' => 0, 'amount' => 2000, 'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
        ];

        $this->actingAs($admin)->post(route('payments.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('payments.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::firstOrFail();
        $this->assertSame('2000.00', $invoice->fresh()->paid);

        $this->actingAs($admin)->delete(route('payments.destroy', $payment))->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('reversed', $payment->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->paid);
    }

    public function test_accountant_can_view_but_cannot_modify_charge_configuration(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
        $student = $this->student();
        $invoice = $this->invoiceWithLine($student, today()->startOfMonth(), 5000);

        $this->actingAs($accountant)->get(route('fees.show', $invoice))->assertOk();
        $this->actingAs($accountant)->post(route('fees.charges.store', $invoice), [])->assertForbidden();
    }

    public function test_fees_workspace_has_four_simple_sections_and_payment_form_has_no_charge_builder(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $invoice = $this->invoiceWithLine($student, today()->startOfMonth(), 5000);

        $this->actingAs($admin)->get(route('fees.index'))->assertOk()
            ->assertSee('Family Fees')->assertSee('Individual Student Fees')
            ->assertSee('Payment History / Ledgers')->assertSee('Fee Settings');
        $this->actingAs($admin)->get(route('fees.settings'))->assertOk()
            ->assertSee('Class-wise monthly fees')->assertSee('Student-specific monthly fee')->assertSee('Late fee rule');
        $this->actingAs($admin)->put(route('fees.settings.update'), [
            'invoice_due_day' => 12, 'late_fee_enabled' => 1, 'late_fee_type' => 'fixed',
            'late_fee_value' => 250, 'late_fee_grace_days' => 3,
        ])->assertRedirect();
        $this->assertDatabaseHas('school_settings', ['key' => 'invoice_due_day', 'value' => '12']);
        $this->actingAs($admin)->get(route('payments.create', ['invoice_id' => $invoice->id]))->assertOk()
            ->assertSee('Amount received')->assertSee('Payment method')->assertSee('Remaining Balance')
            ->assertDontSee('Uniform')->assertDontSee('Paper fund')->assertDontSee('Allocation');
    }

    public function test_student_and_family_ledgers_filter_real_invoices_and_receipts(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $student = $this->student(['family_id' => $family->id]);
        $invoice = $this->invoiceWithLine($student, today()->startOfMonth(), 5000);
        $payment = $this->payment($student, $invoice, 2000);
        $invoice->recalculateTotals();

        $filters = ['month' => today()->format('Y-m')];
        $this->actingAs($admin)->get(route('fees.students.ledger', [$student] + $filters))->assertOk()
            ->assertSee('Student ledger')->assertSee($invoice->invoice_number)->assertSee($payment->receipt_number)->assertSee('3,000.00');
        $this->actingAs($admin)->get(route('fees.families.ledger', [$family] + $filters))->assertOk()
            ->assertSee('Monthly family ledger')->assertSee($invoice->invoice_number)->assertSee($payment->receipt_number)->assertSee('3,000.00');
    }

    public function test_effective_dated_class_fee_applies_only_to_future_generation(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['name' => 'Grade 7', 'monthly_fee' => 4500, 'sort_order' => 7]);
        $student = $this->student(['school_class_id' => $class->id, 'class' => $class->name, 'monthly_fee' => 4000]);
        $november = Carbon::create(2026, 11, 1);
        $december = Carbon::create(2026, 12, 1);

        $this->actingAs($admin)->post(route('fees.class-structures.store', $class), [
            'monthly_fee' => 6200, 'effective_from' => '2026-12', 'reason' => 'Future class revision',
        ])->assertRedirect();
        foreach ([$november, $december] as $month) {
            $this->actingAs($admin)->post(route('fees.generate'), [
                'month' => $month->format('Y-m'), 'due_date' => $month->copy()->day(10)->toDateString(),
            ])->assertRedirect();
        }

        $this->assertSame('4500.00', FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $november)->firstOrFail()->total);
        $this->assertSame('6200.00', FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $december)->firstOrFail()->total);
    }

    public function test_invoice_family_snapshot_keeps_history_after_student_reassignment(): void
    {
        $admin = $this->admin();
        $originalFamily = $this->family();
        $newFamily = Family::create(['display_name' => 'New Family']);
        $student = $this->student(['family_id' => $originalFamily->id]);
        $month = today()->addMonth()->startOfMonth();
        $this->actingAs($admin)->post(route('fees.generate'), [
            'month' => $month->format('Y-m'), 'due_date' => $month->copy()->day(10)->toDateString(),
        ])->assertRedirect();
        $invoice = FeeInvoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame($originalFamily->id, $invoice->family_id);

        $student->update(['family_id' => $newFamily->id]);
        $this->actingAs($admin)->get(route('fees.families.ledger', $originalFamily))->assertOk()
            ->assertSee($invoice->invoice_number)->assertSee($student->name);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function family(): Family
    {
        $guardian = Guardian::create(['name' => 'Muhammad Ahmed', 'relation' => 'father', 'phone' => '03001234567']);
        $family = Family::create(['display_name' => 'Ahmed Family', 'primary_guardian_id' => $guardian->id, 'phone' => $guardian->phone]);
        $family->guardians()->attach($guardian->id, ['relationship_role' => 'father', 'is_primary' => true, 'authorized_fee_payer' => true]);

        return $family;
    }

    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'name' => 'Test Student', 'admission_no' => 'ADM-'.Str::upper(Str::random(6)),
            'father_name' => 'Muhammad Ahmed', 'class' => 'Grade 5', 'campus' => 'campus_1',
            'status' => 'active', 'monthly_fee' => 5000, 'mobile' => '03005550001',
        ], $overrides));
    }

    private function invoiceWithLine(Student $student, Carbon $month, float $amount): FeeInvoice
    {
        $invoice = FeeInvoice::create([
            'student_id' => $student->id,
            'invoice_number' => 'INV-'.$month->format('Ym').'-'.$student->id,
            'billing_month' => $month, 'issued_on' => $month, 'due_date' => $month->copy()->day(10),
            'subtotal' => 0, 'discount' => 0, 'total' => 0, 'paid' => 0, 'status' => 'unpaid',
        ]);
        FeeInvoiceItem::create([
            'fee_invoice_id' => $invoice->id, 'fee_category_id' => FeeCategory::where('code', 'monthly')->value('id'),
            'description' => 'Monthly tuition', 'charge_date' => $month, 'billing_month' => $month,
            'item_type' => 'charge', 'status' => 'active', 'amount' => $amount,
        ]);
        $invoice->recalculateTotals();

        return $invoice->fresh();
    }

    private function payment(Student $student, FeeInvoice $invoice, float $amount): Payment
    {
        return Payment::create([
            'student_id' => $student->id, 'fee_invoice_id' => $invoice->id, 'amount' => $amount,
            'payment_method' => 'cash', 'status' => 'completed', 'monthly_fee_amount' => $amount,
            'uniform_amount' => 0, 'books_amount' => 0, 'paper_fund_amount' => 0, 'other_amount' => 0,
            'previous_balance_amount' => 0, 'discount_amount' => 0, 'total_amount' => $amount,
            'remaining_amount' => 0, 'payment_date' => today(), 'receipt_number' => 'TEST-'.Str::uuid(),
        ]);
    }
}
