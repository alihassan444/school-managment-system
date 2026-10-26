<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_fee_position_uses_active_students_and_current_month_payments(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $firstStudent = Student::create($this->studentAttributes([
            'name' => 'First Student',
            'monthly_fee' => 5000,
        ]));

        Student::create($this->studentAttributes([
            'name' => 'Second Student',
            'monthly_fee' => 3000,
        ]));

        $inactiveStudent = Student::create($this->studentAttributes([
            'name' => 'Inactive Student',
            'status' => 'inactive',
            'monthly_fee' => 9000,
        ]));

        Payment::create([
            'student_id' => $firstStudent->id,
            'amount' => 4500,
            'payment_date' => now()->toDateString(),
            'receipt_number' => 'RCP000001',
        ]);

        Payment::create([
            'student_id' => $firstStudent->id,
            'amount' => 10000,
            'payment_date' => now()->subMonth()->toDateString(),
            'receipt_number' => 'RCP000002',
        ]);

        Payment::create([
            'student_id' => $inactiveStudent->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'receipt_number' => 'RCP000003',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('analytics'));

        $response
            ->assertOk()
            ->assertViewHas('expectedThisMonth', 8000.0)
            ->assertViewHas('collectedThisMonth', 4500.0)
            ->assertViewHas('remainingThisMonth', 3500.0)
            ->assertViewHas('collectionRate', 56)
            ->assertViewHas('dailyFees', 5500.0)
            ->assertViewHas('dailyPayments', fn ($payments) => $payments->count() === 2)
            ->assertSee('Fee left')
            ->assertSee('Rs 3,500');
    }

    public function test_a_selected_date_excludes_records_added_after_that_day(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');

        $student = Student::create($this->studentAttributes([
            'monthly_fee' => 5000,
        ]));
        $student->forceFill(['created_at' => Carbon::parse('2026-01-01 09:00:00')])->save();

        Payment::create([
            'student_id' => $student->id,
            'amount' => 2000,
            'payment_date' => '2026-01-10',
            'receipt_number' => 'RCP000010',
        ]);

        Payment::create([
            'student_id' => $student->id,
            'amount' => 3000,
            'payment_date' => '2026-01-11',
            'receipt_number' => 'RCP000011',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('analytics', [
            'date' => '2026-01-10',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('dailyFees', 2000.0)
            ->assertViewHas('totalFees', 2000.0)
            ->assertViewHas('collectedThisMonth', 2000.0)
            ->assertViewHas('remainingThisMonth', 3000.0)
            ->assertSee('RCP000010')
            ->assertDontSee('RCP000011')
            ->assertSee('Historical view');
    }

    private function studentAttributes(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Student',
            'father_name' => 'Test Parent',
            'class' => 'Grade 5',
            'campus' => 'campus_1',
            'status' => 'active',
            'monthly_fee' => 0,
        ], $overrides);
    }
}
