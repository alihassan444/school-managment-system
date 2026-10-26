<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Family;
use App\Models\FeeInvoice;
use App\Models\SalaryRecord;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Database\Seeders\SchoolSuiteDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSuiteDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_populates_and_renders_every_portal_tab(): void
    {
        $admin = User::factory()->create([
            'name' => 'Demo Administrator',
            'email' => 'admin@school.local',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->seed(SchoolSuiteDemoSeeder::class);

        $this->assertSame(30, Student::where('mobile', 'like', '0300990%')->count());
        $this->assertGreaterThanOrEqual(12, SchoolClass::count());
        $this->assertGreaterThanOrEqual(8, Subject::count());
        $this->assertSame(16, Staff::count());
        $this->assertGreaterThan(100, FeeInvoice::where('invoice_number', 'like', 'DMO-%')->count());
        $this->assertGreaterThan(400, AttendanceRecord::count());
        $this->assertSame(16, Exam::where('name', 'like', 'Demo %')->count());
        $this->assertSame(SchoolClass::count() * 30, TimetableEntry::count());
        $this->assertSame(48, SalaryRecord::count());
        $this->assertSame(6, Family::where('family_code', 'like', 'FAM-DMO-%')->count());
        $this->assertSame(14, Student::whereNotNull('family_id')->count());

        $routes = [
            'dashboard', 'analytics', 'students.index', 'academics.index',
            'fees.index', 'payments.index', 'attendance.index', 'exams.index',
            'reports.index', 'timetable.index', 'staff.index', 'settings.index',
            'families.index', 'family-fees.index', 'reports.families', 'sibling-discounts.index',
        ];

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('financePrivacyToggle', false)
            ->assertSee('Amounts hidden');

        foreach ($routes as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
