<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyPayment;
use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonthlyFinancialIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_switching_months_is_read_only_and_keeps_invoices_isolated(): void
    {
        [$admin, $family, $student] = $this->context();
        $september = $this->invoice($student, '2026-09-01', 10000, 'SEP-ONLY');
        $october = $this->invoice($student, '2026-10-01', 10000, 'OCT-ONLY');
        $november = $this->invoice($student, '2026-11-01', 10000, 'NOV-ONLY');

        $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id, 'month' => '2026-09']))
            ->assertOk()->assertSee('SEP-ONLY')->assertDontSee('OCT-ONLY')->assertDontSee('NOV-ONLY');
        $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id, 'month' => '2026-10']))
            ->assertOk()->assertSee('OCT-ONLY')->assertDontSee('SEP-ONLY')->assertDontSee('NOV-ONLY');
        $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id, 'month' => '2026-09']))
            ->assertOk()->assertSee('SEP-ONLY');

        $this->assertDatabaseCount('fee_invoices', 3);
        $this->assertSame('10000.00', $september->fresh()->total);
        $this->assertSame('10000.00', $october->fresh()->total);
        $this->assertSame('10000.00', $november->fresh()->total);
    }

    public function test_selected_month_summary_separates_current_and_previous_outstanding(): void
    {
        [$admin, $family, $student] = $this->context();
        $september = $this->invoice($student, '2026-09-01', 10000, 'SEP-PARTIAL');
        $october = $this->invoice($student, '2026-10-01', 10000, 'OCT-PARTIAL');
        $this->payment($student, $september, 7000, '2026-09-15', 'SEP-R1');
        $this->payment($student, $october, 5000, '2026-10-12', 'OCT-R1');
        $september->refreshPaymentStatus();
        $october->refreshPaymentStatus();

        $response = $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id, 'month' => '2026-10']));
        $response->assertOk()
            ->assertViewHas('currentFees', 10000.0)
            ->assertViewHas('currentPaid', 5000.0)
            ->assertViewHas('currentOutstanding', 5000.0)
            ->assertViewHas('previousOutstanding', 3000.0)
            ->assertViewHas('totalOutstanding', 8000.0);
    }

    public function test_selected_month_family_payment_never_silently_pays_an_older_invoice(): void
    {
        [$admin, $family, $student] = $this->context();
        $september = $this->invoice($student, '2026-09-01', 3000, 'SEP-DUE');
        $october = $this->invoice($student, '2026-10-01', 5000, 'OCT-DUE');

        $this->actingAs($admin)->post(route('family-fees.payments.store'), [
            'family_id' => $family->id, 'billing_month' => '2026-10', 'payment_scope' => 'selected_month',
            'idempotency_key' => (string) Str::uuid(), 'amount' => 2000, 'payment_method' => 'cash',
            'payment_date' => '2026-10-20',
        ])->assertRedirect();

        $this->assertSame('0.00', $september->fresh()->paid);
        $this->assertSame('2000.00', $october->fresh()->paid);
        $familyPayment = FamilyPayment::firstOrFail();
        $this->assertSame('2026-10-01', $familyPayment->billing_month->toDateString());
        $this->assertSame('selected_month', $familyPayment->payment_scope);
        $this->actingAs($admin)->get(route('family-fees.index', ['family_id' => $family->id, 'month' => '2026-10']))
            ->assertOk()->assertSee($familyPayment->receipt_number)->assertSee('Billing-period payment history');
    }

    public function test_all_outstanding_scope_reconciles_oldest_invoice_first(): void
    {
        [$admin, $family, $student] = $this->context();
        $september = $this->invoice($student, '2026-09-01', 3000, 'SEP-DUE');
        $october = $this->invoice($student, '2026-10-01', 5000, 'OCT-DUE');

        $this->actingAs($admin)->post(route('family-fees.payments.store'), [
            'family_id' => $family->id, 'billing_month' => '2026-10', 'payment_scope' => 'all_outstanding',
            'idempotency_key' => (string) Str::uuid(), 'amount' => 4000, 'payment_method' => 'bank',
            'payment_date' => '2026-10-20',
        ])->assertRedirect();

        $this->assertSame('3000.00', $september->fresh()->paid);
        $this->assertSame('1000.00', $october->fresh()->paid);
    }

    public function test_cross_month_payment_has_separate_billing_and_cash_reporting_perspectives(): void
    {
        [$admin, $family, $student] = $this->context();
        $september = $this->invoice($student, '2026-09-01', 10000, 'SEP-CROSS');
        $this->payment($student, $september, 6000, '2026-10-05', 'CROSS-R1');
        $september->refreshPaymentStatus();

        $septemberReport = $this->actingAs($admin)->get(route('reports.index', ['from' => '2026-09-01', 'to' => '2026-09-30']));
        $septemberReport->assertOk()->assertViewHas('totals', fn ($totals) => $totals['collected'] === 0.0 && $totals['cash_collected'] === 0.0 && $totals['outstanding'] === 10000.0);

        $octoberReport = $this->actingAs($admin)->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']));
        $octoberReport->assertOk()->assertViewHas('totals', fn ($totals) => $totals['invoiced'] === 0.0 && $totals['cash_collected'] === 6000.0);

        $familySeptember = $this->actingAs($admin)->get(route('reports.families', ['family_id' => $family->id, 'from' => '2026-09-01', 'to' => '2026-09-30']));
        $familySeptember->assertOk()->assertViewHas('totals', fn ($totals) => (float) $totals['collected'] === 0.0 && (float) $totals['outstanding'] === 10000.0);
        $familyOctober = $this->actingAs($admin)->get(route('reports.families', ['family_id' => $family->id, 'from' => '2026-10-01', 'to' => '2026-10-31']));
        $familyOctober->assertOk()->assertViewHas('totals', fn ($totals) => (float) $totals['cash_collected'] === 6000.0);

        $this->assertSame('2026-09-01', $september->fresh()->billing_month->toDateString());
        $this->assertSame('2026-10-05', Payment::where('receipt_number', 'CROSS-R1')->firstOrFail()->payment_date->toDateString());
    }

    public function test_monthly_generation_is_idempotent(): void
    {
        [$admin, , $student] = $this->context();
        $payload = ['month' => '2026-10', 'due_date' => '2026-10-10'];
        $this->actingAs($admin)->post(route('fees.generate'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('fees.generate'), $payload)->assertRedirect();
        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', '2026-10-01')->count());
    }

    public function test_family_outstanding_reconciles_to_sum_of_invoice_balances(): void
    {
        [, $family, $student] = $this->context();
        $first = $this->invoice($student, '2026-09-01', 4000, 'SEP-REC');
        $second = $this->invoice($student, '2026-10-01', 5000, 'OCT-REC');
        $this->payment($student, $first, 1500, '2026-10-02', 'REC-R1');
        $first->refreshPaymentStatus();
        $second->refreshPaymentStatus();
        $expected = FeeInvoice::whereHas('student', fn ($query) => $query->where('family_id', $family->id))->get()
            ->sum(fn ($invoice) => max((float) $invoice->total - (float) $invoice->paid, 0));
        $this->assertSame($expected, $family->fresh()->outstanding_balance);
        $this->assertSame(7500.0, $expected);
    }

    private function context(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $guardian = Guardian::create(['name' => 'Test Guardian', 'relation' => 'father', 'phone' => '03000000001']);
        $family = Family::create(['display_name' => 'Test Family', 'primary_guardian_id' => $guardian->id]);
        $student = Student::create(['name' => 'Ali Ahmed', 'father_name' => 'Test Guardian', 'class' => 'Grade 5', 'campus' => 'campus_1', 'status' => 'active', 'monthly_fee' => 10000, 'family_id' => $family->id]);
        return [$admin, $family, $student];
    }

    private function invoice(Student $student, string $month, float $total, string $number): FeeInvoice
    {
        return FeeInvoice::create(['student_id' => $student->id, 'invoice_number' => $number, 'billing_month' => $month,
            'issued_on' => $month, 'due_date' => Carbon::parse($month)->day(10), 'subtotal' => $total, 'total' => $total, 'paid' => 0, 'status' => 'unpaid']);
    }

    private function payment(Student $student, FeeInvoice $invoice, float $amount, string $date, string $receipt): Payment
    {
        return Payment::create(['student_id' => $student->id, 'fee_invoice_id' => $invoice->id, 'amount' => $amount,
            'payment_method' => 'cash', 'status' => 'completed', 'monthly_fee_amount' => $amount, 'uniform_amount' => 0,
            'books_amount' => 0, 'paper_fund_amount' => 0, 'other_amount' => 0, 'previous_balance_amount' => 0,
            'discount_amount' => 0, 'total_amount' => $amount, 'remaining_amount' => 0, 'payment_date' => $date,
            'posted_at' => Carbon::parse($date)->setTime(10, 0), 'receipt_number' => $receipt]);
    }
}
