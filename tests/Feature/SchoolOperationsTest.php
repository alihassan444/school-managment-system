<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\FeeInvoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_structure_can_be_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('academics.classes.store'), [
            'name' => 'Grade 6', 'sort_order' => 6, 'monthly_fee' => 5500,
        ])->assertRedirect();
        $class = SchoolClass::firstOrFail();

        $this->actingAs($user)->post(route('academics.sections.store'), [
            'school_class_id' => $class->id, 'name' => 'A', 'capacity' => 35,
        ])->assertRedirect();
        $this->actingAs($user)->post(route('academics.subjects.store'), [
            'name' => 'Mathematics', 'code' => 'MATH-6',
        ])->assertRedirect();
        $subject = Subject::firstOrFail();
        $this->actingAs($user)->post(route('academics.assign-subject'), [
            'school_class_id' => $class->id, 'subject_id' => $subject->id,
            'max_marks' => 100, 'pass_marks' => 40,
        ])->assertRedirect();

        $this->assertDatabaseHas('sections', ['school_class_id' => $class->id, 'name' => 'A']);
        $this->assertDatabaseHas('class_subject', ['school_class_id' => $class->id, 'subject_id' => $subject->id]);
    }

    public function test_monthly_invoices_and_partial_payments_update_status(): void
    {
        $user = User::factory()->create();
        $student = Student::create($this->studentData(['monthly_fee' => 5000]));

        $this->actingAs($user)->post(route('fees.generate'), [
            'month' => today()->format('Y-m'),
            'due_date' => today()->addDays(5)->toDateString(),
        ])->assertRedirect();
        $invoice = FeeInvoice::firstOrFail();

        $this->actingAs($user)->post(route('payments.store'), [
            'student_id' => $student->id,
            'fee_invoice_id' => $invoice->id,
            'monthly_fee_amount' => 5000,
            'uniform_amount' => 0,
            'books_amount' => 0,
            'paper_fund_amount' => 0,
            'other_amount' => 0,
            'previous_balance_amount' => 0,
            'discount_amount' => 0,
            'amount' => 3000,
            'payment_method' => 'cash',
            'payment_date' => today()->toDateString(),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame('3000.00', $invoice->paid);
    }

    public function test_daily_attendance_can_be_saved_as_a_class_register(): void
    {
        $user = User::factory()->create();
        $class = SchoolClass::create(['name' => 'Grade 7', 'monthly_fee' => 6000]);
        $student = Student::create($this->studentData(['school_class_id' => $class->id, 'class' => $class->name]));

        $this->actingAs($user)->get(route('attendance.index'))->assertOk();
        $this->actingAs($user)->get(route('attendance.index', [
            'date' => today()->toDateString(), 'class_id' => $class->id,
        ]))->assertOk()->assertSee($student->name);

        $this->actingAs($user)->post(route('attendance.store'), [
            'date' => today()->toDateString(),
            'class_id' => $class->id,
            'attendance' => [$student->id => 'present'],
        ])->assertRedirect();

        $record = AttendanceRecord::firstOrFail();
        $this->assertSame($student->id, $record->student_id);
        $this->assertSame(today()->toDateString(), $record->attendance_date->toDateString());
        $this->assertSame('present', $record->status);
    }

    public function test_exam_date_sheet_and_marks_workflow(): void
    {
        $user = User::factory()->create();
        $class = SchoolClass::create(['name' => 'Grade 8', 'monthly_fee' => 6500]);
        $subject = Subject::create(['name' => 'Science', 'code' => 'SCI-8']);
        $class->subjects()->attach($subject->id, ['max_marks' => 100, 'pass_marks' => 40]);
        $student = Student::create($this->studentData(['school_class_id' => $class->id, 'class' => $class->name]));
        $exam = Exam::create([
            'academic_session_id' => AcademicSession::current()->value('id'),
            'school_class_id' => $class->id,
            'name' => 'Final Term',
            'starts_on' => today()->addDay(),
            'ends_on' => today()->addDays(5),
            'status' => 'draft',
        ]);

        $this->actingAs($user)->get(route('exams.show', $exam))
            ->assertOk()
            ->assertSee('data-max-marks="100"', false)
            ->assertSee('data-pass-marks="40"', false);

        $this->actingAs($user)->post(route('exams.subjects.store', $exam), [
            'subject_id' => $subject->id,
            'exam_date' => today()->addDay()->toDateString(),
        ])->assertRedirect();
        $paper = $exam->subjects()->firstOrFail();
        $this->assertSame(100, $paper->max_marks);
        $this->assertSame(40, $paper->pass_marks);
        $this->actingAs($user)->post(route('exams.marks.store', $paper), [
            'marks' => [$student->id => ['value' => 82]],
        ])->assertRedirect();

        $this->assertSame('82.00', ExamMark::firstOrFail()->marks_obtained);
    }

    public function test_timetable_rejects_overlapping_class_periods(): void
    {
        $user = User::factory()->create();
        $class = SchoolClass::create(['name' => 'Grade 9', 'monthly_fee' => 7000]);
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG-9']);
        TimetableEntry::create([
            'school_class_id' => $class->id, 'subject_id' => $subject->id,
            'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '10:00',
        ]);

        $response = $this->actingAs($user)->from(route('timetable.index'))->post(route('timetable.store'), [
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'day_of_week' => 1,
            'starts_at' => '09:30',
            'ends_at' => '10:30',
        ]);

        $response->assertRedirect(route('timetable.index'))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('timetable_entries', 1);
    }

    public function test_inactive_users_are_signed_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_student_promotion_preserves_enrollment_history(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $fromClass = SchoolClass::create(['name' => 'Grade 4', 'monthly_fee' => 4000]);
        $toClass = SchoolClass::create(['name' => 'Grade 5', 'monthly_fee' => 5000]);
        $student = Student::create($this->studentData(['school_class_id' => $fromClass->id, 'class' => $fromClass->name]));
        $session = AcademicSession::current()->firstOrFail();

        $this->actingAs($user)->post(route('students.promote'), [
            'student_ids' => [$student->id],
            'academic_session_id' => $session->id,
            'school_class_id' => $toClass->id,
        ])->assertRedirect(route('students.index'));

        $student->refresh();
        $this->assertSame($toClass->id, $student->school_class_id);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id, 'academic_session_id' => $session->id, 'school_class_id' => $toClass->id,
        ]);
    }

    public function test_roles_protect_administrative_settings(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $this->actingAs($accountant)->get(route('fees.index'))->assertOk();
        $this->actingAs($accountant)->get(route('settings.index'))->assertForbidden();
    }

    public function test_reports_can_be_downloaded_as_csv_and_pdf(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get(route('reports.csv'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($user)->get(route('reports.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    private function studentData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Operations Student',
            'father_name' => 'Operations Parent',
            'class' => 'Grade 5',
            'campus' => 'campus_1',
            'status' => 'active',
            'monthly_fee' => 5000,
        ], $overrides);
    }
}
