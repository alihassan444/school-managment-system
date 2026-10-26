<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FamilyPayment;
use App\Models\FamilyStatement;
use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\SiblingDiscountRule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FamilyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admission_creates_a_family_and_second_admission_reuses_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('students.store'), $this->admissionData([
            'name' => 'Ali Asim', 'family_mode' => 'new', 'family_display_name' => 'Asim Family',
            'guardian_name' => 'Muhammad Asim', 'guardian_phone' => '0300-1234567',
        ]))->assertRedirect();

        $family = Family::firstOrFail();
        $this->assertSame('FAM-0001', $family->family_code);
        $this->assertSame($family->id, Student::where('name', 'Ali Asim')->value('family_id'));
        $this->assertDatabaseHas('family_guardian', ['family_id' => $family->id, 'is_primary' => true]);

        $this->actingAs($admin)->post(route('students.store'), $this->admissionData([
            'name' => 'Sara Asim', 'family_mode' => 'existing', 'family_id' => $family->id,
        ]))->assertRedirect();

        $this->assertDatabaseCount('families', 1);
        $this->assertSame(2, $family->students()->count());
        $this->assertSame($family->primary_guardian_id, Student::where('name', 'Sara Asim')->value('guardian_id'));
    }

    public function test_existing_students_can_be_linked_and_safely_reassigned_without_changing_history(): void
    {
        $admin = $this->admin();
        $first = $this->family('First Family');
        $second = $this->family('Second Family');
        $student = $this->student(['family_id' => $first->id, 'sibling_order' => 1]);
        $invoice = $this->invoice($student, 5000);
        $payment = $this->payment($student, $invoice, 1500);
        $invoice->refreshPaymentStatus();

        $this->actingAs($admin)->from(route('families.show', $second))->post(route('families.students.link', $second), [
            'student_id' => $student->id,
        ])->assertSessionHasErrors('confirm_reassignment');
        $this->assertSame($first->id, $student->fresh()->family_id);

        $this->actingAs($admin)->post(route('families.students.link', $second), [
            'student_id' => $student->id, 'confirm_reassignment' => 1,
        ])->assertRedirect();

        $this->assertSame($second->id, $student->fresh()->family_id);
        $this->assertDatabaseHas('fee_invoices', ['id' => $invoice->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => 1500]);
        $this->assertTrue(AuditLog::where('action', 'family-student-linked')->exists());
    }

    public function test_family_tree_and_financial_pages_render_with_real_relationships(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $first = $this->student(['family_id' => $family->id, 'name' => 'Ali', 'sibling_order' => 1]);
        $second = $this->student(['family_id' => $family->id, 'name' => 'Sara', 'mobile' => '03009998888', 'sibling_order' => 2]);
        $firstInvoice = $this->invoice($first, 4000);
        $secondInvoice = $this->invoice($second, 3500);

        $this->actingAs($admin)->get(route('families.index'))->assertOk()->assertSee($family->family_code);
        $this->actingAs($admin)->get(route('families.show', $family))->assertOk()->assertSee('Family tree')->assertSee('Ali')->assertSee('Sara');
        $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id]))
            ->assertOk()->assertSee('Combined outstanding')->assertSee('7,500')
            ->assertSee('Already paid')->assertSee('Outstanding')
            ->assertSee('Remaining After Payment')
            ->assertSee($firstInvoice->invoice_number)->assertSee($secondInvoice->invoice_number)
            ->assertDontSee('Allocation')->assertDontSee('allocations[', false)
            ->assertDontSee('autoAllocate', false)->assertDontSee('clearAllocations', false);
    }

    public function test_full_and_partial_family_payments_reconcile_each_student_ledger(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $ali = $this->student(['family_id' => $family->id, 'name' => 'Ali']);
        $sara = $this->student(['family_id' => $family->id, 'name' => 'Sara', 'mobile' => '03007770002']);
        $talha = $this->student(['family_id' => $family->id, 'name' => 'Talha', 'class' => 'Grade 2', 'mobile' => '03007770003']);
        $aliInvoice = $this->invoice($ali, 4000);
        $saraInvoice = $this->invoice($sara, 3500);

        $key = (string) Str::uuid();
        $payload = [
            'family_id' => $family->id, 'idempotency_key' => $key, 'amount' => 6000,
            'payment_method' => 'cash', 'payment_date' => today()->toDateString(),
        ];
        $this->actingAs($admin)->post(route('family-fees.payments.store'), $payload)->assertRedirect();

        $familyPayment = FamilyPayment::firstOrFail();
        $this->assertSame('6000.00', $familyPayment->total_amount);
        $this->assertSame(2, $familyPayment->allocations()->count());
        $this->assertSame('paid', $aliInvoice->fresh()->status);
        $this->assertSame('partial', $saraInvoice->fresh()->status);
        $this->assertEquals(6000.0, (float) Payment::completed()->sum('amount'));

        $this->actingAs($admin)->post(route('family-fees.payments.store'), $payload)->assertRedirect(route('family-fees.receipts.show', $familyPayment));
        $this->assertDatabaseCount('family_payments', 1);
        $this->assertDatabaseCount('payments', 2);

        $receipt = $this->actingAs($admin)->get(route('family-fees.receipts.show', $familyPayment));
        $receipt->assertOk()->assertSee('Ali')->assertSee('Sara')->assertSee('Talha')
            ->assertSee('Total Family Fee / Payable')->assertSee('Amount Paid')->assertSee('Remaining Balance')
            ->assertDontSee($aliInvoice->invoice_number)->assertDontSee($saraInvoice->invoice_number)
            ->assertDontSee('Allocated');
        $this->actingAs($admin)->get(route('family-fees.receipts.pdf', $familyPayment))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_overpayment_is_blocked_after_invoice_balance_changes(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $student = $this->student(['family_id' => $family->id]);
        $invoice = $this->invoice($student, 4000);

        $this->actingAs($admin)->post(route('family-fees.payments.store'), [
            'family_id' => $family->id, 'idempotency_key' => (string) Str::uuid(), 'amount' => 3000,
            'payment_method' => 'cash', 'payment_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->actingAs($admin)->from(route('family-fees.index', ['family_id' => $family->id]))->post(route('family-fees.payments.store'), [
            'family_id' => $family->id, 'idempotency_key' => (string) Str::uuid(), 'amount' => 2000,
            'payment_method' => 'cash', 'payment_date' => today()->toDateString(),
        ])->assertSessionHasErrors('amount')->assertSessionDoesntHaveErrors('allocations');
        $this->assertSame('3000.00', $invoice->fresh()->paid);
    }

    public function test_combined_statement_references_original_invoices_without_new_charges(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $first = $this->student(['family_id' => $family->id, 'name' => 'Ali', 'class' => 'Grade 8']);
        $second = $this->student(['family_id' => $family->id, 'name' => 'Hamza', 'class' => 'Grade 5', 'mobile' => '03005550002']);
        $third = $this->student(['family_id' => $family->id, 'name' => 'Talha', 'class' => 'Grade 2', 'mobile' => '03005550003']);
        $one = $this->invoice($first, 4000);
        $two = $this->invoice($second, 3500);
        $three = $this->invoice($third, 2500);

        $this->actingAs($admin)->post(route('family-fees.statements.store'), [
            'family_id' => $family->id, 'invoice_ids' => [$one->id, $two->id, $three->id],
        ])->assertRedirect();
        $statement = FamilyStatement::firstOrFail();
        $this->assertSame('10000.00', $statement->total_amount);
        $this->assertSame(3, $statement->items()->count());
        $this->assertDatabaseCount('fee_invoices', 3);
        $invoice = $this->actingAs($admin)->get(route('family-fees.statements.show', $statement));
        $invoice->assertOk()->assertSee('Ali')->assertSee('Hamza')->assertSee('Talha')
            ->assertSee('Grade 8')->assertSee('Grade 5')->assertSee('Grade 2')
            ->assertSee('Current Month Family Fee')->assertSee('Rs. 10,000')
            ->assertDontSee($one->invoice_number)->assertDontSee($two->invoice_number)->assertDontSee($three->invoice_number)
            ->assertDontSee('Original invoice')->assertDontSee('Fee breakdown');
        $this->actingAs($admin)->get(route('family-fees.statements.pdf', $statement))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_previous_family_balance_is_calculated_from_unpaid_invoices_without_duplicate_charges(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $olderStudent = $this->student(['family_id' => $family->id, 'name' => 'Ali']);
        $currentStudent = $this->student(['family_id' => $family->id, 'name' => 'Hamza', 'mobile' => '03004440002']);
        $older = $this->invoice($olderStudent, 4000, today()->subMonth()->startOfMonth()->toDateString());
        $current = $this->invoice($currentStudent, 5000);
        $this->payment($olderStudent, $older, 1000);
        $this->payment($currentStudent, $current, 2000);
        $older->refreshPaymentStatus();
        $current->refreshPaymentStatus();

        $this->actingAs($admin)->post(route('family-fees.statements.store'), [
            'family_id' => $family->id, 'billing_month' => today()->format('Y-m'),
        ])->assertRedirect();

        $statement = FamilyStatement::firstOrFail();
        $this->assertSame('6000.00', $statement->total_amount);
        $this->assertSame(2, $statement->items()->count());
        $this->assertDatabaseCount('fee_invoices', 2);
        $this->actingAs($admin)->get(route('family-fees.statements.show', $statement))
            ->assertOk()->assertSee('Previous Balance')->assertSee('Rs. 3,000')
            ->assertSee('Current Month Family Fee')->assertSee('Rs. 5,000')
            ->assertSee('Total Payable')->assertSee('Rs. 8,000')
            ->assertSee('Amount Paid')->assertSee('Rs. 2,000')
            ->assertSee('Remaining Balance')->assertSee('Rs. 6,000');
    }

    public function test_sibling_discount_applies_only_to_new_eligible_invoices(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $first = $this->student(['family_id' => $family->id, 'sibling_order' => 1, 'monthly_fee' => 5000]);
        $second = $this->student(['family_id' => $family->id, 'sibling_order' => 2, 'monthly_fee' => 5000, 'mobile' => '03001110002']);
        SiblingDiscountRule::create([
            'name' => 'Second child 10%', 'discount_type' => 'sibling_order', 'discount_value' => 10,
            'minimum_sibling_order' => 2, 'is_active' => true, 'priority' => 10,
        ]);

        $month = today()->addMonth()->format('Y-m');
        $this->actingAs($admin)->post(route('fees.generate'), ['month' => $month, 'due_date' => today()->addMonth()->day(10)->toDateString()])->assertRedirect();
        $this->assertSame('0.00', FeeInvoice::where('student_id', $first->id)->firstOrFail()->sibling_discount);
        $secondInvoice = FeeInvoice::where('student_id', $second->id)->firstOrFail();
        $this->assertSame('500.00', $secondInvoice->sibling_discount);
        $this->assertSame('4500.00', $secondInvoice->total);
    }

    public function test_family_payment_reversal_is_audited_and_reports_do_not_double_count(): void
    {
        $admin = $this->admin();
        $family = $this->family();
        $student = $this->student(['family_id' => $family->id]);
        $invoice = $this->invoice($student, 4000);
        $this->actingAs($admin)->post(route('family-fees.payments.store'), [
            'family_id' => $family->id, 'idempotency_key' => (string) Str::uuid(), 'amount' => 4000,
            'payment_method' => 'bank', 'payment_date' => today()->toDateString(),
        ]);
        $familyPayment = FamilyPayment::firstOrFail();
        $ledgerPayment = Payment::firstOrFail();
        $this->assertSame('completed', $ledgerPayment->status);
        $this->assertSame($family->id, $ledgerPayment->student->family_id);

        $report = $this->actingAs($admin)->get(route('reports.families'));
        $report->assertOk()->assertViewHas('totals');
        $this->assertEquals(4000.0, (float) $report->viewData('totals')['collected']);
        $this->actingAs($admin)->post(route('family-fees.receipts.reverse', $familyPayment))->assertRedirect();
        $this->assertSame('reversed', $familyPayment->fresh()->status);
        $this->assertSame('reversed', Payment::firstOrFail()->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'family-payment-reversed')->exists());
    }

    public function test_unauthorized_teacher_cannot_access_family_finances_or_change_links(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        $family = $this->family();
        $student = $this->student();
        $this->actingAs($teacher)->get(route('family-fees.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('families.students.link', $family), ['student_id' => $student->id])->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function family(string $name = 'Asim Family'): Family
    {
        $guardian = Guardian::create(['name' => 'Muhammad Asim', 'relation' => 'father', 'phone' => '03001234567']);
        $family = Family::create(['display_name' => $name, 'primary_guardian_id' => $guardian->id, 'phone' => $guardian->phone]);
        $family->guardians()->attach($guardian->id, ['relationship_role' => 'father', 'is_primary' => true, 'authorized_fee_payer' => true]);
        return $family;
    }

    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'name' => 'Family Student', 'father_name' => 'Muhammad Asim', 'class' => 'Grade 5',
            'campus' => 'campus_1', 'status' => 'active', 'monthly_fee' => 5000, 'mobile' => '03005550001',
        ], $overrides));
    }

    private function invoice(Student $student, float $total, ?string $billingMonth = null): FeeInvoice
    {
        $month = $billingMonth ? \Illuminate\Support\Carbon::parse($billingMonth)->startOfMonth() : today()->startOfMonth();
        $dueDate = $month->isSameMonth(today()) ? today()->addDays(5) : $month->copy()->addDays(9);
        return FeeInvoice::create([
            'student_id' => $student->id, 'invoice_number' => 'INV-TEST-'.$student->id.'-'.Str::lower(Str::random(5)),
            'billing_month' => $month, 'due_date' => $dueDate,
            'subtotal' => $total, 'total' => $total, 'paid' => 0, 'status' => 'unpaid',
        ]);
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

    private function admissionData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Student', 'father_name' => 'Muhammad Asim', 'class' => 'Grade 1',
            'campus' => 'campus_1', 'status' => 'active', 'monthly_fee' => 4000,
            'mobile' => '03001230001', 'admission_date' => today()->toDateString(),
            'family_mode' => 'none',
        ], $overrides);
    }
}
