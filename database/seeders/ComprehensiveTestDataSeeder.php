<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Family;
use App\Models\FamilyPayment;
use App\Models\FamilyPaymentAllocation;
use App\Models\FeeCategory;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class ComprehensiveTestDataSeeder extends Seeder
{
    private Carbon $asOf;

    public function run(): void
    {
        $allowed = app()->environment('testing')
            || (app()->environment('local') && filter_var(env('ALLOW_TEST_FIXTURES', false), FILTER_VALIDATE_BOOLEAN));

        if (! $allowed || app()->environment('production')) {
            throw new LogicException('Comprehensive test fixtures are disabled. Use APP_ENV=testing, or local with ALLOW_TEST_FIXTURES=true, on an isolated database.');
        }

        $this->asOf = Carbon::create(2026, 10, 24, 12);

        DB::transaction(function (): void {
            $admin = User::updateOrCreate(['email' => 'test.admin@school.invalid'], [
                'name' => 'Test School Administrator', 'role' => 'admin', 'is_active' => true,
                'password' => Hash::make('TestOnly@2026!'),
            ]);
            $session = AcademicSession::updateOrCreate(['name' => '2026-2027'], [
                'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31', 'is_current' => true,
            ]);
            $classes = $this->seedClasses();
            $staff = $this->seedStaff();
            $subjects = $this->seedSubjects($classes, $staff);
            [$families, $students] = $this->seedFamiliesAndStudents($session, $classes);
            $this->seedInvoicesAndPayments($students, $session, $admin);
            $this->groupFamilyPayments($families, $admin);
            $this->seedAttendance($students, $admin);
            $this->seedExams($classes, $subjects, $students, $session);
            $this->seedTimetable($classes, $subjects, $staff);
            $this->seedPayroll($staff);
        });

        $this->command?->info('Isolated fixtures ready: 100 students, 20 families, 11 classes, 18 staff, four billing months, attendance, exams, timetable and payroll.');
    }

    private function seedClasses()
    {
        $rows = [
            ['Playgroup', 3500], ['Nursery', 3800], ['Prep', 4200], ['Grade 1', 4600],
            ['Grade 2', 4900], ['Grade 3', 5200], ['Grade 4', 5600], ['Grade 5', 6000],
            ['Grade 6', 6500], ['Grade 7', 7000], ['Grade 8', 7500],
        ];

        foreach ($rows as $index => [$name, $fee]) {
            $class = SchoolClass::updateOrCreate(['name' => $name], [
                'sort_order' => $index + 1, 'monthly_fee' => $fee, 'is_active' => true,
            ]);
            foreach (['A', 'B'] as $sectionName) {
                Section::updateOrCreate(['school_class_id' => $class->id, 'name' => $sectionName], ['capacity' => 35]);
            }
        }

        return SchoolClass::with('sections')->whereIn('name', collect($rows)->pluck(0))->orderBy('sort_order')->get();
    }

    private function seedStaff()
    {
        $names = [
            ['Ayesha Malik', 'administrator'], ['Usman Tariq', 'accountant'],
            ['Sana Javed', 'teacher'], ['Hira Ahmed', 'teacher'], ['Bilal Hassan', 'teacher'],
            ['Mariam Raza', 'teacher'], ['Ali Hamza', 'teacher'], ['Noor Fatima', 'teacher'],
            ['Zain Qureshi', 'teacher'], ['Rabia Siddiqua', 'teacher'], ['Omer Farooq', 'teacher'],
            ['Mahnoor Aslam', 'teacher'], ['Danish Iqbal', 'teacher'], ['Amna Khalid', 'teacher'],
            ['Kashif Mehmood', 'support'], ['Saima Bashir', 'support'], ['Naveed Akram', 'support'], ['Farah Shah', 'support'],
        ];
        foreach ($names as $index => [$name, $role]) {
            Staff::updateOrCreate(['employee_no' => 'TST-EMP-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)], [
                'name' => $name, 'role' => $role, 'department' => $role === 'teacher' ? 'Academics' : ucfirst($role),
                'phone' => '0300881'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'email' => 'staff'.($index + 1).'@test.invalid', 'joined_on' => '2024-04-01',
                'basic_salary' => $role === 'teacher' ? 58000 + ($index * 700) : 45000 + ($index * 900),
                'status' => 'active', 'address' => 'TEST DATA — Lahore',
            ]);
        }
        return Staff::where('employee_no', 'like', 'TST-EMP-%')->orderBy('employee_no')->get();
    }

    private function seedSubjects($classes, $staff)
    {
        $rows = [
            ['English', 'TST-ENG'], ['Urdu', 'TST-URD'], ['Mathematics', 'TST-MATH'],
            ['General Science', 'TST-SCI'], ['Computer Studies', 'TST-COMP'], ['Islamiyat', 'TST-ISL'],
            ['Social Studies', 'TST-SST'], ['Art', 'TST-ART'], ['General Knowledge', 'TST-GK'], ['Physical Education', 'TST-PE'],
        ];
        foreach ($rows as [$name, $code]) Subject::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        $subjects = Subject::where('code', 'like', 'TST-%')->orderBy('id')->get();
        $teachers = $staff->where('role', 'teacher')->values();
        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                $class->subjects()->syncWithoutDetaching([$subject->id => [
                    'teacher_id' => $teachers[($classIndex + $subjectIndex) % $teachers->count()]->id,
                    'max_marks' => 100, 'pass_marks' => 40,
                ]]);
            }
        }
        return $subjects;
    }

    private function seedFamiliesAndStudents(AcademicSession $session, $classes): array
    {
        $surnames = ['Ahmed', 'Khan', 'Raza', 'Qureshi', 'Iqbal', 'Malik', 'Siddiqui', 'Hassan', 'Tariq', 'Noor', 'Farooq', 'Aslam', 'Javed', 'Akram', 'Shah', 'Butt', 'Sheikh', 'Chaudhry', 'Mughal', 'Awan'];
        $firstNames = ['Ali', 'Hamza', 'Talha', 'Ayaan', 'Huzaifa', 'Maryam', 'Zainab', 'Abdullah', 'Sara', 'Ahmed', 'Fatima', 'Ibrahim', 'Hania', 'Rayyan', 'Ayesha', 'Musa', 'Mahnoor', 'Daniyal', 'Kinza', 'Arham'];
        $families = collect();
        $students = collect();

        foreach ($surnames as $familyIndex => $surname) {
            $number = $familyIndex + 1;
            $guardian = Guardian::updateOrCreate(['phone' => '0300882'.str_pad((string) $number, 4, '0', STR_PAD_LEFT)], [
                'name' => ['Muhammad', 'Imran', 'Faisal', 'Asif', 'Javed'][$familyIndex % 5].' '.$surname,
                'relation' => 'father', 'normalized_phone' => '0300882'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'occupation' => ['Teacher', 'Engineer', 'Business', 'Accountant', 'Government Service'][$familyIndex % 5],
                'email' => 'guardian'.$number.'@test.invalid', 'address' => 'TEST DATA — Lahore, Punjab',
            ]);
            $family = Family::updateOrCreate(['family_code' => 'TST-FAM-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT)], [
                'display_name' => $surname.' Test Family', 'primary_guardian_id' => $guardian->id,
                'phone' => $guardian->phone, 'address' => 'TEST DATA — Lahore, Punjab', 'notes' => 'Comprehensive isolated fixture',
            ]);
            $family->guardians()->syncWithoutDetaching([$guardian->id => [
                'relationship_role' => 'father', 'is_primary' => true, 'authorized_fee_payer' => true,
            ]]);
            $families->push($family);

            foreach (range(1, 5) as $childIndex) {
                $studentNumber = ($familyIndex * 5) + $childIndex;
                $class = $classes[($studentNumber - 1) % $classes->count()];
                $section = $class->sections[($studentNumber + $familyIndex) % 2];
                $name = $firstNames[($studentNumber - 1) % count($firstNames)].' '.$surname;
                $student = Student::updateOrCreate(['admission_no' => 'TST-'.str_pad((string) $studentNumber, 4, '0', STR_PAD_LEFT)], [
                    'name' => $name, 'father_name' => $guardian->name, 'date_of_birth' => Carbon::create(2012, 1, 1)->subMonths($studentNumber)->toDateString(),
                    'gender' => $studentNumber % 2 ? 'male' : 'female', 'admission_date' => '2026-04-05',
                    'class' => $class->name, 'campus' => $studentNumber % 2 ? 'campus_1' : 'campus_2',
                    'academic_session_id' => $session->id, 'school_class_id' => $class->id, 'section_id' => $section->id,
                    'guardian_id' => $guardian->id, 'family_id' => $family->id, 'sibling_order' => $childIndex,
                    'roll_no' => str_pad((string) $class->sort_order, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $studentNumber, 3, '0', STR_PAD_LEFT),
                    'mobile' => '0300883'.str_pad((string) $studentNumber, 4, '0', STR_PAD_LEFT),
                    'contact' => '042-388'.str_pad((string) $studentNumber, 4, '0', STR_PAD_LEFT),
                    'address' => 'TEST DATA — Lahore, Punjab', 'status' => 'active', 'monthly_fee' => $class->monthly_fee,
                ]);
                $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship_role' => 'father', 'is_primary' => true]]);
                DB::table('enrollments')->updateOrInsert(
                    ['student_id' => $student->id, 'academic_session_id' => $session->id],
                    ['school_class_id' => $class->id, 'section_id' => $section->id, 'roll_no' => $student->roll_no, 'status' => 'enrolled', 'enrolled_on' => '2026-04-05', 'created_at' => now(), 'updated_at' => now()]
                );
                $students->push($student);
            }
        }
        return [$families, $students];
    }

    private function seedInvoicesAndPayments($students, AcademicSession $session, User $admin): void
    {
        $category = FeeCategory::where('code', 'monthly')->first();
        foreach (['2026-08-01', '2026-09-01', '2026-10-01', '2026-11-01'] as $monthValue) {
            $month = Carbon::parse($monthValue);
            foreach ($students as $index => $student) {
                $total = (float) $student->monthly_fee;
                $invoice = FeeInvoice::where('student_id', $student->id)->whereDate('billing_month', $month->toDateString())->first();
                $values = ['academic_session_id' => $session->id, 'invoice_number' => 'TST-INV-'.$month->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        'issued_on' => $month->copy()->day(1), 'due_date' => $month->copy()->day(10), 'subtotal' => $total,
                        'discount' => 0, 'total' => $total, 'notes' => 'Comprehensive isolated fixture'];
                if ($invoice) {
                    $invoice->update($values);
                } else {
                    $invoice = FeeInvoice::create(['student_id' => $student->id, 'billing_month' => $month->toDateString()] + $values);
                }
                $invoice->items()->updateOrCreate(['description' => 'Monthly Fee — '.$month->format('F Y')], ['fee_category_id' => $category?->id, 'amount' => $total]);

                $scenario = $index % 5;
                $amount = match ($scenario) {
                    0 => $total,
                    1 => round($total * 0.55, 2),
                    2 => 0,
                    3 => $month->month === 9 ? round($total * 0.40, 2) : ($month->month <= 10 ? $total : 0),
                    default => $month->month === 8 ? $total : ($month->month === 9 ? $total : ($month->month === 10 ? round($total * 0.80, 2) : 0)),
                };
                if ($month->month === 11) $amount = 0;
                if ($amount > 0) {
                    $paymentDate = ($scenario === 4 && $month->month === 9)
                        ? Carbon::create(2026, 10, 5)
                        : $month->copy()->day(min(8 + ($index % 12), $month->daysInMonth));
                    Payment::updateOrCreate(['receipt_number' => 'TST-R-'.$month->format('Ym').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)], [
                        'student_id' => $student->id, 'fee_invoice_id' => $invoice->id, 'recorded_by' => $admin->id,
                        'amount' => $amount, 'payment_method' => $index % 4 === 0 ? 'bank' : 'cash', 'status' => 'completed',
                        'monthly_fee_amount' => $amount, 'uniform_amount' => 0, 'books_amount' => 0, 'paper_fund_amount' => 0,
                        'other_amount' => 0, 'previous_balance_amount' => 0, 'discount_amount' => 0, 'total_amount' => $amount,
                        'remaining_amount' => max($total - $amount, 0), 'description' => 'TEST DATA payment',
                        'payment_date' => $paymentDate, 'posted_at' => $paymentDate->copy()->setTime(10, 0),
                    ]);
                }
                $invoice->refreshPaymentStatus();
            }
        }
    }

    private function groupFamilyPayments($families, User $admin): void
    {
        foreach ($families->take(5) as $index => $family) {
            $payments = Payment::with('invoice')->completed()->whereNull('family_payment_id')
                ->whereHas('student', fn ($query) => $query->where('family_id', $family->id))
                ->whereHas('invoice', fn ($query) => $query->whereDate('billing_month', '2026-10-01'))->get();
            if ($payments->isEmpty()) continue;
            $familyPayment = FamilyPayment::updateOrCreate(['idempotency_key' => '00000000-0000-4000-8000-'.str_pad((string) ($index + 1), 12, '0', STR_PAD_LEFT)], [
                'family_id' => $family->id, 'billing_month' => '2026-10-01', 'payment_scope' => 'selected_month',
                'receipt_number' => 'TST-FR-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'total_amount' => $payments->sum('amount'), 'payment_method' => 'cash', 'payment_date' => '2026-10-12',
                'received_by' => $admin->id, 'status' => 'completed', 'notes' => 'TEST DATA combined family payment',
            ]);
            foreach ($payments as $payment) {
                $payment->update(['family_payment_id' => $familyPayment->id]);
                FamilyPaymentAllocation::updateOrCreate(
                    ['family_payment_id' => $familyPayment->id, 'payment_id' => $payment->id],
                    ['student_id' => $payment->student_id, 'fee_invoice_id' => $payment->fee_invoice_id, 'amount' => $payment->amount]
                );
            }
        }
    }

    private function seedAttendance($students, User $admin): void
    {
        foreach (range(1, 18) as $offset) {
            $date = $this->asOf->copy()->subDays($offset);
            if ($date->isSunday()) continue;
            foreach ($students as $index => $student) {
                $code = ($index + $offset) % 19;
                $record = AttendanceRecord::where('student_id', $student->id)->whereDate('attendance_date', $date->toDateString())->first();
                $values = [
                    'status' => $code === 0 ? 'leave' : ($code < 3 ? 'absent' : ($code === 3 ? 'late' : 'present')),
                    'remarks' => $code === 0 ? 'Approved test leave' : null, 'marked_by' => $admin->id,
                ];
                $record ? $record->update($values) : AttendanceRecord::create(['student_id' => $student->id, 'attendance_date' => $date->toDateString()] + $values);
            }
        }
    }

    private function seedExams($classes, $subjects, $students, AcademicSession $session): void
    {
        foreach ($classes as $class) {
            $exam = Exam::updateOrCreate(['school_class_id' => $class->id, 'name' => 'TST Mid Term 2026'], [
                'academic_session_id' => $session->id, 'starts_on' => '2026-09-14', 'ends_on' => '2026-09-19', 'status' => 'completed',
            ]);
            foreach ($subjects->take(5)->values() as $subjectIndex => $subject) {
                $paper = $exam->subjects()->updateOrCreate(['subject_id' => $subject->id], [
                    'exam_date' => Carbon::parse('2026-09-14')->addDays($subjectIndex), 'starts_at' => '09:00', 'ends_at' => '11:00', 'max_marks' => 100, 'pass_marks' => 40,
                ]);
                foreach ($students->where('school_class_id', $class->id) as $student) {
                    ExamMark::updateOrCreate(['exam_subject_id' => $paper->id, 'student_id' => $student->id], [
                        'marks_obtained' => 45 + (($student->id + $subjectIndex * 7) % 51), 'is_absent' => false,
                    ]);
                }
            }
        }
    }

    private function seedTimetable($classes, $subjects, $staff): void
    {
        $teachers = $staff->where('role', 'teacher')->values();
        $periods = [['08:00', '08:45'], ['08:45', '09:30'], ['09:45', '10:30'], ['10:30', '11:15']];
        foreach ($classes as $classIndex => $class) foreach (range(1, 5) as $day) foreach ($periods as $periodIndex => [$start, $end]) {
            TimetableEntry::updateOrCreate(
                ['school_class_id' => $class->id, 'section_id' => $class->sections->first()->id, 'day_of_week' => $day, 'starts_at' => $start],
                ['subject_id' => $subjects[($classIndex + $day + $periodIndex) % $subjects->count()]->id,
                    'staff_id' => $teachers[($classIndex + $periodIndex) % $teachers->count()]->id, 'ends_at' => $end, 'room' => 'TST-'.$class->sort_order]
            );
        }
    }

    private function seedPayroll($staff): void
    {
        foreach (['2026-08-01', '2026-09-01', '2026-10-01'] as $month) foreach ($staff as $index => $member) {
            $allowances = $member->role === 'teacher' ? 2500 : 3500;
            $deductions = $index % 7 === 0 ? 1000 : 0;
            $record = SalaryRecord::where('staff_id', $member->id)->whereDate('salary_month', $month)->first();
            $values = [
                'basic_salary' => $member->basic_salary, 'allowances' => $allowances, 'deductions' => $deductions,
                'net_salary' => (float) $member->basic_salary + $allowances - $deductions,
                'paid_on' => Carbon::parse($month)->day(28), 'status' => 'paid',
                'reference_number' => 'TST-PAY-'.Carbon::parse($month)->format('Ym').'-'.$member->employee_no,
            ];
            $record ? $record->update($values) : SalaryRecord::create(['staff_id' => $member->id, 'salary_month' => $month] + $values);
        }
    }
}
