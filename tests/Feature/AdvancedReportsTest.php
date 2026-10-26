<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\ExamSubject;
use App\Models\Family;
use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\SalaryRecord;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdvancedReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $accountant;

    private AcademicSession $session;

    private SchoolClass $gradeOne;

    private SchoolClass $gradeTwo;

    private Section $sectionA;

    private Section $sectionB;

    private Student $ali;

    private Student $sara;

    private Student $talha;

    private Exam $exam;

    private Staff $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 12:00:00');
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
        $this->session = AcademicSession::current()->firstOrFail();
        $this->gradeOne = SchoolClass::create(['name' => 'Grade 1', 'sort_order' => 1, 'monthly_fee' => 5000]);
        $this->gradeTwo = SchoolClass::create(['name' => 'Grade 2', 'sort_order' => 2, 'monthly_fee' => 6000]);
        $this->teacher = Staff::create(['employee_no' => 'EMP-001', 'name' => 'Class Teacher', 'role' => 'teacher', 'basic_salary' => 30000, 'status' => 'active']);
        $this->sectionA = Section::create(['school_class_id' => $this->gradeOne->id, 'class_teacher_id' => $this->teacher->id, 'name' => 'A']);
        $this->sectionB = Section::create(['school_class_id' => $this->gradeTwo->id, 'name' => 'B']);
        $guardian = Guardian::create(['name' => 'Ahmed Khan', 'relation' => 'father', 'phone' => '03000000001']);
        $family = Family::create(['display_name' => 'Ahmed Family', 'primary_guardian_id' => $guardian->id]);

        $this->ali = $this->student('Ali Ahmed', 'G1-01', $this->gradeOne, $this->sectionA, '2026-04-01', $guardian, $family);
        $this->sara = $this->student('Sara Fatima', 'G1-02', $this->gradeOne, $this->sectionA, '2026-04-01', $guardian, $family);
        $this->talha = $this->student('Talha Raza', 'G2-01', $this->gradeTwo, $this->sectionB, '2026-10-15', $guardian);

        $this->attendance($this->ali, '2026-10-01', 'present');
        $this->attendance($this->ali, '2026-10-02', 'absent');
        $this->attendance($this->ali, '2026-10-03', 'leave');
        $this->attendance($this->sara, '2026-10-01', 'present');
        $this->attendance($this->sara, '2026-10-02', 'late');
        $this->attendance($this->sara, '2026-10-03', 'present');
        $this->attendance($this->sara, '2026-10-16', 'present');
        $this->attendance($this->talha, '2026-10-16', 'present');

        $september = $this->invoice($this->ali, '2026-09-01', 5000, 'SEP-ALI');
        $octoberAli = $this->invoice($this->ali, '2026-10-01', 5000, 'OCT-ALI');
        $octoberSara = $this->invoice($this->sara, '2026-10-01', 5000, 'OCT-SARA');
        $this->payment($this->ali, $september, 2000, '2026-10-05', 'RPT-CROSS');
        $this->payment($this->ali, $octoberAli, 5000, '2026-10-10', 'RPT-ALI');
        $this->payment($this->sara, $octoberSara, 2500, '2026-10-11', 'RPT-SARA');

        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'RPT-MATH']);
        $this->exam = Exam::create(['academic_session_id' => $this->session->id, 'school_class_id' => $this->gradeOne->id, 'name' => 'Mid Term', 'starts_on' => '2026-10-12', 'ends_on' => '2026-10-16', 'status' => 'completed']);
        $paper = ExamSubject::create(['exam_id' => $this->exam->id, 'subject_id' => $subject->id, 'exam_date' => '2026-10-12', 'max_marks' => 100, 'pass_marks' => 40]);
        ExamMark::create(['exam_subject_id' => $paper->id, 'student_id' => $this->ali->id, 'marks_obtained' => 82]);
        ExamMark::create(['exam_subject_id' => $paper->id, 'student_id' => $this->sara->id, 'marks_obtained' => 34]);

        SalaryRecord::create(['staff_id' => $this->teacher->id, 'salary_month' => '2026-10-01', 'basic_salary' => 30000, 'allowances' => 2000, 'deductions' => 1000, 'net_salary' => 31000, 'status' => 'paid', 'paid_on' => '2026-10-05']);
        TimetableEntry::create(['school_class_id' => $this->gradeOne->id, 'section_id' => $this->sectionA->id, 'subject_id' => $subject->id, 'staff_id' => $this->teacher->id, 'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '10:00', 'room' => 'G1-A']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_report_selector_lists_every_supported_functional_report(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));
        $response->assertOk();
        foreach (['Fee Collection Report', 'Paid / Unpaid Fees Report', 'Outstanding Dues Report', 'Family Fee Collection Report', 'Monthly Attendance Report', 'Student Attendance History', 'Students / Enrollment Report', 'Exams & Results Report', 'Student Academic Performance Report', 'Staff Salary Report', 'Timetable Report'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee('data-report-filter="student"', false)->assertSee('data-report-filter="exam"', false);
    }

    public function test_every_report_type_runs_against_real_models(): void
    {
        $params = [
            'fee_collection' => ['month' => '2026-10'], 'fee_status' => ['month' => '2026-10'],
            'outstanding_dues' => ['month' => '2026-10'], 'family_fees' => ['month' => '2026-10'],
            'monthly_attendance' => ['month' => '2026-10', 'class_id' => $this->gradeOne->id],
            'student_attendance' => ['period_mode' => 'range', 'from' => '2026-10-01', 'to' => '2026-10-31'],
            'students' => ['period_mode' => 'all'],
            'exams' => ['exam_id' => $this->exam->id], 'student_performance' => ['exam_id' => $this->exam->id],
            'staff_salary' => ['month' => '2026-10'], 'timetable' => ['class_id' => $this->gradeOne->id],
        ];
        foreach ($params as $type => $filters) {
            $this->actingAs($this->admin)->get(route('reports.index', ['report_type' => $type] + $filters))
                ->assertOk()->assertViewHas('report', fn ($report) => $report['type'] === $type);
        }
    }

    public function test_monthly_attendance_counts_present_absent_leave_late_and_missing_records_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'monthly_attendance', 'class_id' => $this->gradeOne->id,
            'section_id' => $this->sectionA->id, 'month' => '2026-10',
        ]));
        $response->assertOk()->assertViewHas('report', function ($report): bool {
            $ali = $report['rows']->firstWhere('student', 'Ali Ahmed');
            $sara = $report['rows']->firstWhere('student', 'Sara Fatima');

            return $ali['present'] === 1 && $ali['absent'] === 1 && $ali['leave'] === 1 && $ali['late'] === 0
                && $ali['marked'] === 3 && $ali['not_marked'] === 1 && $ali['percentage'] === 33.3
                && $sara['present'] === 3 && $sara['late'] === 1 && $sara['percentage'] === 100.0;
        });
    }

    public function test_mid_month_admission_only_uses_eligible_recorded_days(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'monthly_attendance', 'class_id' => $this->gradeTwo->id,
            'section_id' => $this->sectionB->id, 'month' => '2026-10',
        ]))->assertOk()->assertViewHas('report', function ($report): bool {
            $talha = $report['rows']->firstWhere('student', 'Talha Raza');

            return $talha['marked'] === 1 && $talha['present'] === 1 && $talha['not_marked'] === 0 && $talha['percentage'] === 100.0;
        });
    }

    public function test_individual_attendance_has_daily_breakdown_and_not_marked_status(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'monthly_attendance', 'class_id' => $this->gradeOne->id,
            'student_id' => $this->ali->id, 'month' => '2026-10',
        ]));
        $response->assertOk()->assertSee('Student Attendance Report')->assertSee('Not Marked')
            ->assertViewHas('report', fn ($report) => $report['rows']->count() === 4 && $report['rows']->where('status', 'not marked')->count() === 1);
    }

    public function test_class_section_and_student_endpoint_filters_are_dependent(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('reports.students', [
            'class_id' => $this->gradeOne->id, 'section_id' => $this->sectionA->id,
        ]));
        $response->assertOk()->assertJsonCount(2)->assertJsonFragment(['name' => 'Ali Ahmed'])->assertJsonMissing(['name' => 'Talha Raza']);
    }

    public function test_custom_attendance_date_range_excludes_outside_records(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'student_attendance', 'period_mode' => 'range',
            'from' => '2026-10-01', 'to' => '2026-10-02', 'class_id' => $this->gradeOne->id,
        ]))->assertOk()->assertViewHas('report', function ($report): bool {
            $ali = $report['rows']->firstWhere('student', 'Ali Ahmed');

            return $ali['present'] === 1 && $ali['absent'] === 1 && $ali['leave'] === 0 && $ali['marked'] === 2;
        });
    }

    public function test_month_navigation_preserves_report_scope(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'monthly_attendance', 'class_id' => $this->gradeOne->id, 'month' => '2026-10',
        ]))->assertOk()->assertSee('month=2026-09', false)->assertSee('month=2026-11', false);
    }

    public function test_fee_report_keeps_billing_month_and_cash_collection_dimensions_separate(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'fee_collection', 'period_mode' => 'monthly', 'month' => '2026-10',
        ]))->assertOk()->assertViewHas('report', fn ($report) => $report['rows']->sum('total') === 10000.0
            && $report['rows']->sum('paid') === 7500.0
            && collect($report['stats'])->firstWhere('label', 'Cash received in period')['value'] === 'Rs 9,500'
        );
    }

    public function test_exam_class_and_individual_reports_reuse_subject_marks(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'exams', 'exam_id' => $this->exam->id,
        ]))->assertOk()->assertSee('82.0%')->assertSee('Fail');

        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'student_performance', 'exam_id' => $this->exam->id, 'student_id' => $this->ali->id,
        ]))->assertOk()->assertSee('Mathematics')->assertSee('82');
    }

    public function test_students_family_and_timetable_reports_apply_filters(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'students', 'period_mode' => 'all', 'class_id' => $this->gradeOne->id,
        ]))->assertOk()->assertSee('Ali Ahmed')->assertDontSee('Talha Raza');

        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'family_fees', 'period_mode' => 'monthly', 'month' => '2026-10',
        ]))->assertOk()->assertSee('FAM-0001')->assertSee('Ahmed Khan')->assertSee('Rs 10,000');

        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'timetable', 'class_id' => $this->gradeOne->id,
        ]))->assertOk()->assertSee('Mathematics')->assertSee('Class Teacher');
    }

    public function test_salary_report_is_admin_only_and_supports_month_status_and_staff_filters(): void
    {
        $query = ['report_type' => 'staff_salary', 'month' => '2026-10', 'staff_id' => $this->teacher->id, 'status' => 'paid'];
        $this->actingAs($this->accountant)->get(route('reports.index', $query))->assertForbidden();
        $this->actingAs($this->admin)->get(route('reports.index', $query))->assertOk()->assertSee('Rs 31,000')->assertSee('Class Teacher');
    }

    public function test_preview_print_pdf_and_csv_share_the_same_attendance_filters(): void
    {
        $query = ['report_type' => 'monthly_attendance', 'class_id' => $this->gradeOne->id, 'section_id' => $this->sectionA->id, 'month' => '2026-10'];
        $this->actingAs($this->admin)->get(route('reports.index', $query))->assertOk()->assertSee('Ali Ahmed')->assertDontSee('Talha Raza');
        $this->actingAs($this->admin)->get(route('reports.print', $query))->assertOk()->assertSee('Ali Ahmed')->assertDontSee('portal-sidebar');
        $csv = $this->actingAs($this->admin)->get(route('reports.csv', $query));
        $csv->assertOk();
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('Ali Ahmed', $csvContent);
        $this->assertStringNotContainsString('Talha Raza', $csvContent);
        $this->actingAs($this->admin)->get(route('reports.pdf', $query))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_empty_reports_and_invalid_ranges_are_handled_safely(): void
    {
        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'monthly_attendance', 'month' => '2025-01', 'class_id' => $this->gradeOne->id,
        ]))->assertOk()->assertSee('Recorded school days')->assertSee('Average attendance');

        $this->actingAs($this->admin)->get(route('reports.index', [
            'report_type' => 'student_attendance', 'period_mode' => 'range', 'from' => '2026-10-20', 'to' => '2026-10-01',
        ]))->assertSessionHasErrors('to');
    }

    private function student(string $name, string $roll, SchoolClass $class, Section $section, string $admitted, Guardian $guardian, ?Family $family = null): Student
    {
        $student = Student::create(['name' => $name, 'roll_no' => $roll, 'father_name' => $guardian->name, 'guardian_id' => $guardian->id,
            'family_id' => $family?->id, 'admission_date' => $admitted, 'class' => $class->name, 'campus' => 'campus_1',
            'academic_session_id' => $this->session->id, 'school_class_id' => $class->id, 'section_id' => $section->id,
            'status' => 'active', 'monthly_fee' => $class->monthly_fee]);
        Enrollment::create(['student_id' => $student->id, 'academic_session_id' => $this->session->id, 'school_class_id' => $class->id,
            'section_id' => $section->id, 'roll_no' => $roll, 'status' => 'enrolled', 'enrolled_on' => $admitted]);

        return $student;
    }

    private function attendance(Student $student, string $date, string $status): void
    {
        AttendanceRecord::create(['student_id' => $student->id, 'attendance_date' => $date, 'status' => $status, 'marked_by' => $this->admin->id]);
    }

    private function invoice(Student $student, string $month, float $total, string $number): FeeInvoice
    {
        return FeeInvoice::create(['student_id' => $student->id, 'academic_session_id' => $this->session->id, 'invoice_number' => $number,
            'billing_month' => $month, 'issued_on' => $month, 'due_date' => Carbon::parse($month)->day(10),
            'subtotal' => $total, 'total' => $total, 'paid' => 0, 'status' => 'unpaid']);
    }

    private function payment(Student $student, FeeInvoice $invoice, float $amount, string $date, string $receipt): Payment
    {
        $payment = Payment::create(['student_id' => $student->id, 'fee_invoice_id' => $invoice->id, 'amount' => $amount,
            'payment_method' => 'cash', 'status' => 'completed', 'monthly_fee_amount' => $amount, 'uniform_amount' => 0,
            'books_amount' => 0, 'paper_fund_amount' => 0, 'other_amount' => 0, 'previous_balance_amount' => 0,
            'discount_amount' => 0, 'total_amount' => $amount, 'remaining_amount' => 0, 'payment_date' => $date,
            'posted_at' => Carbon::parse($date)->setTime(10, 0), 'receipt_number' => $receipt]);
        $invoice->refreshPaymentStatus();

        return $payment;
    }
}
