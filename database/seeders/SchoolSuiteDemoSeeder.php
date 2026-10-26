<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\SalaryRecord;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolSuiteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);

        DB::transaction(function (): void {
            $session = AcademicSession::current()->firstOrFail();
            $students = Student::where('mobile', 'like', '0300990%')->orderBy('id')->get();
            $this->ensureDemoClasses($students, $session);
            $students = Student::with('schoolClass')->where('mobile', 'like', '0300990%')->orderBy('id')->get();
            $classes = SchoolClass::with('sections')->orderBy('sort_order')->get();
            $staff = $this->seedStaff();
            $subjects = $this->seedAcademics($classes, $staff);
            $this->enrichStudents($students, $session);
            $this->seedInvoices($students, $session);
            $this->seedAttendance($students);
            $this->seedExams($classes, $subjects, $students, $session);
            $this->seedTimetable($classes, $subjects, $staff);
            $this->seedPayroll($staff);
            $this->seedSettingsAndUsers();
        });

        $this->call(FamilyTreeDemoSeeder::class);

        $this->printSummary();
    }

    private function ensureDemoClasses($students, AcademicSession $session): void
    {
        $classNames = $students->pluck('class')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        foreach ($classNames as $index => $className) {
            $classStudents = $students->where('class', $className);
            $class = SchoolClass::firstOrCreate(
                ['name' => $className],
                [
                    'sort_order' => $index + 1,
                    'monthly_fee' => round((float) $classStudents->avg('monthly_fee'), 2),
                    'is_active' => true,
                ]
            );
            $section = $class->sections()->firstOrCreate(
                ['name' => 'A'],
                ['capacity' => 35]
            );

            foreach ($classStudents as $student) {
                $student->forceFill([
                    'academic_session_id' => $session->id,
                    'school_class_id' => $class->id,
                    'section_id' => $section->id,
                ])->saveQuietly();
            }
        }
    }

    private function seedStaff()
    {
        $people = [
            ['EMP-001', 'Ayesha Malik', 'administrator', 'Administration', 85000],
            ['EMP-002', 'Usman Tariq', 'accountant', 'Accounts', 72000],
            ['EMP-003', 'Sana Javed', 'teacher', 'Early Years', 52000],
            ['EMP-004', 'Hira Ahmed', 'teacher', 'Primary', 54000],
            ['EMP-005', 'Bilal Hassan', 'teacher', 'Primary', 56000],
            ['EMP-006', 'Mariam Raza', 'teacher', 'Primary', 57000],
            ['EMP-007', 'Ali Hamza', 'teacher', 'Middle', 60000],
            ['EMP-008', 'Noor Fatima', 'teacher', 'Middle', 61000],
            ['EMP-009', 'Zain Qureshi', 'teacher', 'Middle', 62000],
            ['EMP-010', 'Rabia Siddiqua', 'teacher', 'Secondary', 65000],
            ['EMP-011', 'Omer Farooq', 'teacher', 'Secondary', 66000],
            ['EMP-012', 'Mahnoor Aslam', 'teacher', 'Secondary', 67000],
            ['EMP-013', 'Danish Iqbal', 'teacher', 'Science', 69000],
            ['EMP-014', 'Amna Khalid', 'teacher', 'Languages', 64000],
            ['EMP-015', 'Kashif Mehmood', 'support', 'Operations', 42000],
            ['EMP-016', 'Sidra Naveed', 'teacher', 'Early Years', 55000],
        ];

        foreach ($people as $index => [$employeeNo, $name, $role, $department, $salary]) {
            Staff::updateOrCreate(['employee_no' => $employeeNo], [
                'name' => $name,
                'role' => $role,
                'department' => $department,
                'phone' => '0301'.str_pad((string) (5500000 + $index), 7, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $name)).'@demo.school.local',
                'joined_on' => today()->subYears(2)->addMonths($index)->toDateString(),
                'basic_salary' => $salary,
                'status' => 'active',
                'address' => 'Lahore, Punjab',
            ]);
        }

        return Staff::orderBy('employee_no')->get();
    }

    private function seedAcademics($classes, $staff)
    {
        $subjectRows = [
            ['English', 'ENG'], ['Urdu', 'URD'], ['Mathematics', 'MATH'],
            ['General Science', 'SCI'], ['Computer Studies', 'COMP'],
            ['Islamiyat', 'ISL'], ['Social Studies', 'SST'], ['Art & Creativity', 'ART'],
        ];
        foreach ($subjectRows as [$name, $code]) {
            Subject::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
        $subjects = Subject::whereIn('code', collect($subjectRows)->pluck(1))->orderBy('id')->get();
        $teachers = $staff->where('role', 'teacher')->values();

        foreach ($classes as $classIndex => $class) {
            $teacher = $teachers[$classIndex % max($teachers->count(), 1)] ?? null;
            $section = $class->sections->first();
            $section?->update(['class_teacher_id' => $teacher?->id, 'capacity' => 35]);

            foreach ($subjects as $subjectIndex => $subject) {
                $subjectTeacher = $teachers[($classIndex + $subjectIndex) % max($teachers->count(), 1)] ?? null;
                $class->subjects()->syncWithoutDetaching([
                    $subject->id => ['teacher_id' => $subjectTeacher?->id, 'max_marks' => 100, 'pass_marks' => 40],
                ]);
            }
        }

        return $subjects;
    }

    private function enrichStudents($students, AcademicSession $session): void
    {
        foreach ($students as $index => $student) {
            $number = $index + 1;
            $guardian = Guardian::updateOrCreate(
                ['phone' => '0315'.str_pad((string) (7700000 + $number), 7, '0', STR_PAD_LEFT)],
                [
                    'name' => $student->father_name,
                    'relation' => $number % 5 === 0 ? 'mother' : 'father',
                    'alternate_phone' => $student->mobile,
                    'cnic' => '35202-'.str_pad((string) (1000000 + $number), 7, '0', STR_PAD_LEFT).'-'.(($number % 9) + 1),
                    'occupation' => ['Business', 'Teacher', 'Engineer', 'Government Service', 'Doctor'][$index % 5],
                    'email' => 'guardian'.$number.'@demo.school.local',
                    'address' => $student->address,
                ]
            );
            $classSequence = max((int) ($student->schoolClass?->sort_order ?? 1), 1);
            $student->update([
                'admission_no' => 'DMO-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'roll_no' => str_pad((string) $classSequence, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'date_of_birth' => today()->subYears(4 + min($classSequence, 12))->subDays($number * 11)->toDateString(),
                'gender' => $number % 2 === 0 ? 'female' : 'male',
                'admission_date' => Carbon::parse($student->created_at)->toDateString(),
                'guardian_id' => $guardian->id,
                'academic_session_id' => $session->id,
            ]);
            DB::table('enrollments')->updateOrInsert(
                ['student_id' => $student->id, 'academic_session_id' => $session->id],
                [
                    'school_class_id' => $student->school_class_id,
                    'section_id' => $student->section_id,
                    'roll_no' => $student->roll_no,
                    'status' => $student->status === 'active' ? 'enrolled' : 'withdrawn',
                    'enrolled_on' => $student->admission_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedInvoices($students, AcademicSession $session): void
    {
        $monthlyCategory = FeeCategory::where('code', 'monthly')->first();
        $paperCategory = FeeCategory::where('code', 'paper-fund')->first();

        foreach (range(4, 0) as $monthsAgo) {
            $month = today()->subMonths($monthsAgo)->startOfMonth();
            foreach ($students as $index => $student) {
                if ($monthsAgo === 0 && $student->status !== 'active') continue;

                $monthPayments = $student->payments()
                    ->where('receipt_number', 'like', 'DMO%')
                    ->whereYear('payment_date', $month->year)
                    ->whereMonth('payment_date', $month->month)
                    ->get();
                $paperFund = $monthsAgo === 0 && ($index + 1) % 3 === 0 ? 400 : 0;
                $paymentTotal = (float) $monthPayments->max('total_amount');
                $subtotal = max((float) $student->monthly_fee + $paperFund, $paymentTotal);
                $invoice = FeeInvoice::updateOrCreate(
                    ['student_id' => $student->id, 'billing_month' => $month->toDateString()],
                    [
                        'academic_session_id' => $session->id,
                        'invoice_number' => 'DMO-INV-'.$month->format('Ym').'-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
                        'due_date' => $month->copy()->day(10)->toDateString(),
                        'subtotal' => $subtotal,
                        'discount' => 0,
                        'total' => $subtotal,
                        'notes' => 'Demo monthly invoice',
                    ]
                );
                $invoice->items()->updateOrCreate(
                    ['description' => 'Monthly Fee — '.$month->format('F Y')],
                    ['fee_category_id' => $monthlyCategory?->id, 'amount' => $student->monthly_fee]
                );
                if ($paperFund > 0) {
                    $invoice->items()->updateOrCreate(
                        ['description' => 'Paper Fund — '.$month->format('F Y')],
                        ['fee_category_id' => $paperCategory?->id, 'amount' => $paperFund]
                    );
                }
                foreach ($monthPayments as $payment) $payment->update(['fee_invoice_id' => $invoice->id]);
                $invoice->refreshPaymentStatus();
            }
        }
    }

    private function seedAttendance($students): void
    {
        foreach (range(20, 0) as $daysAgo) {
            $date = today()->subDays($daysAgo);
            if ($date->isSunday()) continue;
            foreach ($students->where('status', 'active')->values() as $index => $student) {
                $code = ($student->id + $daysAgo) % 17;
                $status = $code === 0 ? 'leave' : ($code <= 2 ? 'absent' : ($code === 3 ? 'late' : 'present'));
                AttendanceRecord::updateOrCreate(
                    ['student_id' => $student->id, 'attendance_date' => $date->toDateString()],
                    ['status' => $status, 'remarks' => $status === 'leave' ? 'Approved demo leave' : null]
                );
            }
        }
    }

    private function seedExams($classes, $subjects, $students, AcademicSession $session): void
    {
        foreach ($classes->filter(fn ($class) => $students->where('school_class_id', $class->id)->isNotEmpty())->take(8) as $classIndex => $class) {
            $completed = Exam::updateOrCreate(
                ['school_class_id' => $class->id, 'name' => 'Demo Mid Term 2026'],
                ['academic_session_id' => $session->id, 'starts_on' => '2026-09-14', 'ends_on' => '2026-09-19', 'status' => 'completed']
            );
            $upcoming = Exam::updateOrCreate(
                ['school_class_id' => $class->id, 'name' => 'Demo Final Term 2026'],
                ['academic_session_id' => $session->id, 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-23', 'status' => 'published']
            );

            foreach ($subjects->take(6)->values() as $subjectIndex => $subject) {
                $completedPaper = $completed->subjects()->updateOrCreate(['subject_id' => $subject->id], [
                    'exam_date' => Carbon::parse('2026-09-14')->addDays($subjectIndex)->toDateString(),
                    'starts_at' => '09:00', 'ends_at' => '11:00', 'max_marks' => 100, 'pass_marks' => 40,
                ]);
                $upcoming->subjects()->updateOrCreate(['subject_id' => $subject->id], [
                    'exam_date' => Carbon::parse('2026-11-16')->addDays($subjectIndex + intdiv($subjectIndex, 5))->toDateString(),
                    'starts_at' => '09:00', 'ends_at' => '11:00', 'max_marks' => 100, 'pass_marks' => 40,
                ]);
                foreach ($students->where('school_class_id', $class->id) as $student) {
                    $absent = ($student->id + $subjectIndex) % 19 === 0;
                    ExamMark::updateOrCreate(
                        ['exam_subject_id' => $completedPaper->id, 'student_id' => $student->id],
                        ['marks_obtained' => $absent ? null : 48 + (($student->id * 7 + $subjectIndex * 5) % 48), 'is_absent' => $absent]
                    );
                }
            }
        }
    }

    private function seedTimetable($classes, $subjects, $staff): void
    {
        $teachers = $staff->where('role', 'teacher')->values();
        $periods = [['08:00', '08:45'], ['08:45', '09:30'], ['09:45', '10:30'], ['10:30', '11:15'], ['11:15', '12:00']];
        foreach ($classes as $classIndex => $class) {
            $teacher = $teachers[$classIndex % max($teachers->count(), 1)] ?? null;
            foreach (range(1, 6) as $day) {
                foreach ($periods as $periodIndex => [$starts, $ends]) {
                    $subject = $subjects[($periodIndex + $day + $classIndex) % $subjects->count()];
                    TimetableEntry::updateOrCreate(
                        ['school_class_id' => $class->id, 'section_id' => $class->sections->first()?->id, 'day_of_week' => $day, 'starts_at' => $starts],
                        ['subject_id' => $subject->id, 'staff_id' => $teacher?->id, 'ends_at' => $ends, 'room' => 'Room '.($classIndex + 1)]
                    );
                }
            }
        }
    }

    private function seedPayroll($staff): void
    {
        foreach (range(2, 0) as $monthsAgo) {
            $month = today()->subMonths($monthsAgo)->startOfMonth();
            foreach ($staff as $index => $member) {
                $allowances = $member->role === 'teacher' ? 2500 : 4000;
                $deductions = ($index + $monthsAgo) % 6 === 0 ? 1200 : 0;
                $paid = $monthsAgo > 0 || $index % 4 !== 0;
                SalaryRecord::updateOrCreate(
                    ['staff_id' => $member->id, 'salary_month' => $month->toDateString()],
                    [
                        'basic_salary' => $member->basic_salary,
                        'allowances' => $allowances,
                        'deductions' => $deductions,
                        'net_salary' => (float) $member->basic_salary + $allowances - $deductions,
                        'paid_on' => $paid ? $month->copy()->day(28)->toDateString() : null,
                        'status' => $paid ? 'paid' : 'unpaid',
                        'reference_number' => $paid ? 'PAY-'.$month->format('Ym').'-'.$member->employee_no : null,
                    ]
                );
            }
        }
    }

    private function seedSettingsAndUsers(): void
    {
        $settings = [
            'school_phone' => '0300-6694291',
            'school_email' => 'office@alsuffaa.demo',
            'school_address' => 'Gulshan Town, Thokar Niaz Baig, Lahore',
        ];
        foreach ($settings as $key => $value) {
            SchoolSetting::updateOrCreate(['key' => $key], ['group' => 'general', 'value' => $value]);
        }

        User::updateOrCreate(['email' => 'accountant.demo@school.local'], [
            'name' => 'Demo Accountant', 'role' => 'accountant', 'is_active' => true, 'password' => Hash::make('Demo@2026!'),
        ]);
        User::updateOrCreate(['email' => 'teacher.demo@school.local'], [
            'name' => 'Demo Teacher', 'role' => 'teacher', 'is_active' => true, 'password' => Hash::make('Demo@2026!'),
        ]);

        $adminId = User::where('email', 'admin@school.local')->value('id');
        foreach (range(1, 8) as $number) {
            AuditLog::updateOrCreate(
                ['action' => 'demo-import-'.$number, 'auditable_type' => Student::class, 'auditable_id' => $number],
                [
                    'user_id' => $adminId,
                    'old_values' => null,
                    'new_values' => ['source' => 'SchoolSuiteDemoSeeder', 'batch' => $number],
                    'ip_address' => '127.0.0.1',
                ]
            );
        }
    }

    private function printSummary(): void
    {
        $counts = [
            'students' => Student::where('mobile', 'like', '0300990%')->count(),
            'classes' => SchoolClass::count(),
            'subjects' => Subject::count(),
            'staff' => Staff::count(),
            'invoices' => FeeInvoice::where('invoice_number', 'like', 'DMO-%')->count(),
            'attendance' => AttendanceRecord::count(),
            'exams' => Exam::where('name', 'like', 'Demo %')->count(),
            'timetable periods' => TimetableEntry::count(),
            'salary records' => SalaryRecord::count(),
        ];
        $this->command?->info('School-suite demo data ready: '.collect($counts)->map(fn ($count, $label) => "{$count} {$label}")->implode(', ').'.');
    }
}
