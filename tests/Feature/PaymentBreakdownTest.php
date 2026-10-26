<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_fee_breakdown_is_calculated_and_saved(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs(User::factory()->create())->post(route('payments.store'), [
            'student_id' => $student->id,
            'monthly_fee_amount' => 5000,
            'uniform_amount' => 1000,
            'books_amount' => 800,
            'paper_fund_amount' => 400,
            'other_amount' => 200,
            'previous_balance_amount' => 600,
            'discount_amount' => 500,
            'amount' => 6000,
            'payment_date' => today()->toDateString(),
            'description' => 'Complete fee slip test',
        ]);

        $payment = Payment::firstOrFail();

        $response->assertRedirect(route('payments.show', $payment));
        $this->assertSame('7500.00', $payment->total_amount);
        $this->assertSame('6000.00', $payment->amount);
        $this->assertSame('1500.00', $payment->remaining_amount);

        $this->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Paper Fund')
            ->assertSee('Previous Balance')
            ->assertSee('1,500');
    }

    public function test_paid_amount_cannot_exceed_final_total(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs(User::factory()->create())->from(route('payments.create'))->post(route('payments.store'), [
            'student_id' => $student->id,
            'monthly_fee_amount' => 5000,
            'uniform_amount' => 0,
            'books_amount' => 0,
            'paper_fund_amount' => 0,
            'other_amount' => 0,
            'previous_balance_amount' => 0,
            'discount_amount' => 0,
            'amount' => 6000,
            'payment_date' => today()->toDateString(),
        ]);

        $response->assertRedirect(route('payments.create'))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    private function createStudent(): Student
    {
        return Student::create([
            'name' => 'Receipt Student',
            'father_name' => 'Receipt Parent',
            'class' => 'Grade 5',
            'campus' => 'campus_1',
            'status' => 'active',
            'monthly_fee' => 5000,
        ]);
    }
}
