<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Family;
use App\Models\FamilyPayment;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\SalaryRecord;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Database\Seeders\ComprehensiveTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveTestDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_comprehensive_fixture_is_realistic_guarded_and_idempotent(): void
    {
        $this->seed(ComprehensiveTestDataSeeder::class);

        $this->assertSame(100, Student::where('admission_no', 'like', 'TST-%')->count());
        $this->assertSame(20, Family::where('family_code', 'like', 'TST-FAM-%')->count());
        $this->assertSame(11, SchoolClass::whereIn('name', ['Playgroup','Nursery','Prep','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8'])->count());
        $this->assertSame(18, Staff::where('employee_no', 'like', 'TST-EMP-%')->count());
        $this->assertSame(400, FeeInvoice::where('invoice_number', 'like', 'TST-INV-%')->count());
        $this->assertGreaterThan(0, Payment::where('receipt_number', 'like', 'TST-R-%')->count());
        $this->assertSame(5, FamilyPayment::where('receipt_number', 'like', 'TST-FR-%')->count());
        $this->assertGreaterThan(1000, AttendanceRecord::count());
        $this->assertSame(54, SalaryRecord::where('reference_number', 'like', 'TST-PAY-%')->count());

        $before = [Student::count(), Family::count(), FeeInvoice::count(), Payment::count()];
        $this->seed(ComprehensiveTestDataSeeder::class);
        $this->assertSame($before, [Student::count(), Family::count(), FeeInvoice::count(), Payment::count()]);

        $crossMonth = Payment::where('receipt_number', 'like', 'TST-R-202609-%')->whereDate('payment_date', '2026-10-05')->firstOrFail();
        $this->assertSame('2026-09-01', $crossMonth->invoice->billing_month->toDateString());
    }
}
