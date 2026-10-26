<?php

namespace App\Services\Reports;

use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\Family;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\SalaryRecord;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SchoolReportService
{
    public const REPORT_TYPES = [
        'fee_collection' => 'Fee Collection Report',
        'fee_status' => 'Paid / Unpaid Fees Report',
        'outstanding_dues' => 'Outstanding Dues Report',
        'family_fees' => 'Family Fee Collection Report',
        'monthly_attendance' => 'Monthly Attendance Report',
        'student_attendance' => 'Student Attendance History',
        'students' => 'Students / Enrollment Report',
        'exams' => 'Exams & Results Report',
        'student_performance' => 'Student Academic Performance Report',
        'staff_salary' => 'Staff Salary Report',
        'timetable' => 'Timetable Report',
    ];

    public const FILTERS = [
        'fee_collection' => ['class', 'section', 'student', 'session', 'period', 'status'],
        'fee_status' => ['class', 'section', 'student', 'session', 'period', 'status'],
        'outstanding_dues' => ['class', 'section', 'student', 'session', 'period'],
        'family_fees' => ['family', 'class', 'section', 'period', 'status'],
        'monthly_attendance' => ['class', 'section', 'student', 'session', 'month'],
        'student_attendance' => ['class', 'section', 'student', 'session', 'period'],
        'students' => ['class', 'section', 'student', 'session', 'period'],
        'exams' => ['class', 'section', 'student', 'session', 'exam'],
        'student_performance' => ['class', 'section', 'student', 'session', 'exam'],
        'staff_salary' => ['staff', 'month', 'status'],
        'timetable' => ['class', 'section'],
    ];

    public function reportTypes(User $user): array
    {
        return collect(self::REPORT_TYPES)
            ->when(($user->role ?: 'admin') !== 'admin', fn ($types) => $types->except('staff_salary'))
            ->all();
    }

    public function filterOptions(Request $request): array
    {
        return [
            'classes' => SchoolClass::with('sections')->orderBy('sort_order')->get(),
            'students' => $this->studentOptions($request),
            'sessions' => AcademicSession::orderByDesc('starts_on')->get(),
            'exams' => Exam::with('schoolClass')->orderByDesc('starts_on')->get(),
            'families' => Family::with('primaryGuardian')->orderBy('family_code')->get(),
            'staffMembers' => Staff::orderBy('name')->get(),
        ];
    }

    public function studentOptions(Request $request): Collection
    {
        return $this->studentQuery($request, false)
            ->select(['students.id', 'students.name', 'students.roll_no', 'students.school_class_id', 'students.section_id'])
            ->limit(500)
            ->get();
    }

    public function generate(Request $request): array
    {
        $type = $request->input('report_type', 'fee_collection');
        $period = $this->period($request, $type);

        return match ($type) {
            'fee_collection', 'fee_status', 'outstanding_dues' => $this->feeReport($request, $period, $type),
            'family_fees' => $this->familyReport($request, $period),
            'monthly_attendance', 'student_attendance' => $this->attendanceReport($request, $period, $type),
            'students' => $this->studentReport($request, $period),
            'exams', 'student_performance' => $this->examReport($request, $type),
            'staff_salary' => $this->salaryReport($request, $period),
            'timetable' => $this->timetableReport($request),
            default => $this->feeReport($request, $period, 'fee_collection'),
        };
    }

    private function feeReport(Request $request, array $period, string $type): array
    {
        $invoiceQuery = FeeInvoice::with(['student.schoolClass', 'student.section'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('billing_month', '>=', $period['from']->copy()->startOfMonth()->toDateString())
            ->whereDate('billing_month', '<=', $period['to']->copy()->endOfMonth()->toDateString());
        $this->applyStudentFiltersToRelation($invoiceQuery, $request);
        $invoices = $invoiceQuery->orderBy('billing_month')->orderBy('student_id')->get();

        $invoiceIds = $invoices->pluck('id');
        $applied = Payment::completed()->whereIn('fee_invoice_id', $invoiceIds)
            ->whereDate('payment_date', '<=', $period['to']->toDateString())
            ->selectRaw('fee_invoice_id, SUM(amount) as total')->groupBy('fee_invoice_id')->pluck('total', 'fee_invoice_id');

        $rows = $invoices->map(function (FeeInvoice $invoice) use ($applied): array {
            $paid = (float) ($applied[$invoice->id] ?? 0);
            $outstanding = max((float) $invoice->total - $paid, 0);
            $status = $outstanding <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            return [
                'billing_month' => $invoice->billing_month->format('M Y'),
                'roll_no' => $invoice->student->roll_no ?: '—',
                'student' => $invoice->student->name,
                'class' => $invoice->student->schoolClass?->name ?: $invoice->student->class,
                'section' => $invoice->student->section?->name ?: '—',
                'invoice' => $invoice->invoice_number,
                'total' => (float) $invoice->total,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'status' => $status,
            ];
        });

        $statusFilter = $request->string('status')->toString();
        if (in_array($statusFilter, ['paid', 'partial', 'unpaid'], true)) {
            $rows = $rows->where('status', $statusFilter)->values();
        }
        if ($type === 'outstanding_dues') {
            $rows = $rows->where('outstanding', '>', 0)->values();
        }

        $studentIds = $this->studentQuery($request, false)->pluck('students.id');
        $cashPayments = Payment::with(['student.schoolClass', 'invoice'])->completed()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('payment_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->get();

        $previousOutstanding = 0.0;
        if ($period['mode'] !== 'all') {
            $previousInvoices = FeeInvoice::whereIn('student_id', $studentIds)
                ->whereDate('billing_month', '<', $period['from']->copy()->startOfMonth()->toDateString())
                ->where('status', '!=', 'cancelled');
            $previousDue = (float) (clone $previousInvoices)->sum('total');
            $previousPaid = (float) Payment::completed()
                ->whereIn('fee_invoice_id', (clone $previousInvoices)->pluck('id'))
                ->whereDate('payment_date', '<=', $period['to']->toDateString())->sum('amount');
            $previousOutstanding = max($previousDue - $previousPaid, 0);
        }

        $totals = [
            'invoiced' => (float) $rows->sum('total'),
            'collected' => (float) $rows->sum('paid'),
            'cash_collected' => (float) $cashPayments->sum('amount'),
            'outstanding' => (float) $rows->sum('outstanding'),
            'previous_outstanding' => $previousOutstanding,
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
        ];

        $title = self::REPORT_TYPES[$type];
        $result = $this->baseResult($type, $title, $period, [
            ['key' => 'billing_month', 'label' => 'Billing Month'],
            ['key' => 'roll_no', 'label' => 'Roll No'],
            ['key' => 'student', 'label' => 'Student'],
            ['key' => 'class', 'label' => 'Class'],
            ['key' => 'section', 'label' => 'Section'],
            ['key' => 'total', 'label' => 'Invoice Amount', 'type' => 'money'],
            ['key' => 'paid', 'label' => 'Paid', 'type' => 'money'],
            ['key' => 'outstanding', 'label' => 'Outstanding', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ], $rows);
        $result['stats'] = [
            ['label' => 'Total invoiced', 'value' => $this->money($totals['invoiced']), 'tone' => 'warning'],
            ['label' => 'Applied to invoices', 'value' => $this->money($totals['collected']), 'tone' => 'success'],
            ['label' => 'Cash received in period', 'value' => $this->money($totals['cash_collected']), 'tone' => 'info'],
            ['label' => 'Period outstanding', 'value' => $this->money($totals['outstanding']), 'tone' => 'danger'],
            ['label' => 'Paid / Partial / Unpaid', 'value' => $rows->where('status', 'paid')->count().' / '.$rows->where('status', 'partial')->count().' / '.$rows->where('status', 'unpaid')->count(), 'tone' => 'primary'],
        ];
        $result['notes'] = [
            'Invoice totals follow the original billing month. Cash received is shown separately by payment date.',
            'Previous outstanding before this period: '.$this->money($previousOutstanding).'.',
        ];
        $result['legacy'] = [
            'from' => $period['from'], 'to' => $period['to'],
            'classId' => $request->integer('class_id') ?: null,
            'payments' => $cashPayments,
            'studentCount' => $studentIds->count(),
            'totals' => $totals,
        ];

        return $result;
    }

    private function attendanceReport(Request $request, array $period, string $type): array
    {
        $cohortRequest = clone $request;
        $cohortRequest->merge(['student_id' => null]);
        $cohort = $this->studentQuery($cohortRequest, true)->get();
        $allRecords = AttendanceRecord::whereIn('student_id', $cohort->pluck('id'))
            ->whereDate('attendance_date', '>=', $period['from']->toDateString())
            ->whereDate('attendance_date', '<=', $period['to']->toDateString())
            ->orderBy('attendance_date')->get();
        $schoolDates = $allRecords->pluck('attendance_date')->map->toDateString()->unique()->sort()->values();
        $recordsByStudent = $allRecords->groupBy('student_id');
        $selectedId = $request->integer('student_id') ?: null;
        $students = $selectedId ? $cohort->where('id', $selectedId)->values() : $cohort;

        $sessionId = $request->integer('academic_session_id') ?: null;
        $summaryRows = $students->map(function (Student $student) use ($recordsByStudent, $schoolDates, $period, $sessionId): array {
            $eligibleDate = $this->eligibleFrom($student, $period['from'], $sessionId)->toDateString();
            $records = ($recordsByStudent[$student->id] ?? collect())
                ->filter(fn ($record) => $record->attendance_date->toDateString() >= $eligibleDate);
            $present = $records->where('status', 'present')->count();
            $absent = $records->where('status', 'absent')->count();
            $leave = $records->where('status', 'leave')->count();
            $late = $records->where('status', 'late')->count();
            $marked = $present + $absent + $leave + $late;
            $eligibleSchoolDays = $schoolDates->filter(fn ($date) => $date >= $eligibleDate)->count();

            return [
                'roll_no' => $student->roll_no ?: '—',
                'student' => $student->name,
                'class' => $this->studentClass($student, $sessionId),
                'section' => $this->studentSection($student, $sessionId),
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'late' => $late,
                'marked' => $marked,
                'not_marked' => max($eligibleSchoolDays - $marked, 0),
                'percentage' => $marked > 0 ? round((($present + $late) / $marked) * 100, 1) : null,
            ];
        });

        $isIndividual = $selectedId && $students->isNotEmpty();
        if ($isIndividual) {
            $student = $students->first();
            $studentRecords = ($recordsByStudent[$student->id] ?? collect())->keyBy(fn ($record) => $record->attendance_date->toDateString());
            $eligibleDate = $this->eligibleFrom($student, $period['from'], $sessionId)->toDateString();
            $rows = $schoolDates->filter(fn ($date) => $date >= $eligibleDate)->map(function ($date) use ($studentRecords): array {
                $record = $studentRecords->get($date);

                return [
                    'date' => Carbon::parse($date)->format('d M Y'),
                    'day' => Carbon::parse($date)->format('l'),
                    'status' => $record?->status ?: 'not marked',
                    'remarks' => $record?->remarks ?: '—',
                ];
            })->values();
            $columns = [
                ['key' => 'date', 'label' => 'Date'], ['key' => 'day', 'label' => 'Day'],
                ['key' => 'status', 'label' => 'Attendance Status', 'type' => 'status'],
                ['key' => 'remarks', 'label' => 'Remarks'],
            ];
            $title = 'Student Attendance Report — '.$student->name;
        } else {
            $rows = $summaryRows;
            $columns = [
                ['key' => 'roll_no', 'label' => 'Roll No'], ['key' => 'student', 'label' => 'Student Name'],
                ['key' => 'present', 'label' => 'Present'], ['key' => 'absent', 'label' => 'Absent'],
                ['key' => 'leave', 'label' => 'Leave'], ['key' => 'late', 'label' => 'Late'],
                ['key' => 'marked', 'label' => 'Total Marked Days'],
                ['key' => 'percentage', 'label' => 'Attendance %', 'type' => 'percentage'],
            ];
            $title = self::REPORT_TYPES[$type];
        }

        $totalMarked = (int) $summaryRows->sum('marked');
        $attended = (int) $summaryRows->sum(fn ($row) => $row['present'] + $row['late']);
        $result = $this->baseResult($type, $title, $period, $columns, $rows);
        $result['stats'] = [
            ['label' => 'Total students', 'value' => $students->count(), 'tone' => 'primary'],
            ['label' => 'Recorded school days', 'value' => $schoolDates->count(), 'tone' => 'info'],
            ['label' => 'Present student-days', 'value' => $summaryRows->sum('present'), 'tone' => 'success'],
            ['label' => 'Absent student-days', 'value' => $summaryRows->sum('absent'), 'tone' => 'danger'],
            ['label' => 'Leave student-days', 'value' => $summaryRows->sum('leave'), 'tone' => 'warning'],
            ['label' => 'Average attendance', 'value' => $totalMarked ? round(($attended / $totalMarked) * 100, 1).'%' : '—', 'tone' => 'primary'],
        ];
        $result['context'] = [
            'Class' => $request->integer('class_id') ? SchoolClass::find($request->integer('class_id'))?->name : 'Whole school',
            'Section' => $request->integer('section_id') ? \App\Models\Section::find($request->integer('section_id'))?->name : 'All sections',
            'Academic Session' => $request->integer('academic_session_id') ? AcademicSession::find($request->integer('academic_session_id'))?->name : (AcademicSession::current()->value('name') ?: 'Current'),
        ];
        $result['notes'] = [
            'Recorded school days are dates with at least one saved attendance entry for the selected cohort; weekends, holidays and missing dates are not treated as absences.',
            'Attendance percentage = (Present + Late) ÷ Total Marked Days. Late is a mutually exclusive status and is not counted twice.',
            'Students admitted mid-month are calculated only from their admission date. Missing entries remain Not Marked.',
        ];
        $result['signatures'] = true;

        return $result;
    }

    private function studentReport(Request $request, array $period): array
    {
        $query = $this->studentQuery($request, true)->with('guardian');
        if ($period['mode'] !== 'all') {
            $query->whereDate('admission_date', '>=', $period['from']->toDateString())
                ->whereDate('admission_date', '<=', $period['to']->toDateString());
        }
        $students = $query->get();
        $sessionId = $request->integer('academic_session_id') ?: null;
        $rows = $students->map(fn (Student $student) => [
            'roll_no' => $student->roll_no ?: '—',
            'student' => $student->name,
            'guardian' => $student->guardian?->name ?: $student->father_name,
            'admission_date' => $student->admission_date?->format('d M Y') ?: '—',
            'class' => $this->studentClass($student, $sessionId),
            'section' => $this->studentSection($student, $sessionId),
            'status' => $student->status,
        ]);

        $result = $this->baseResult('students', self::REPORT_TYPES['students'], $period, [
            ['key' => 'roll_no', 'label' => 'Roll Number'], ['key' => 'student', 'label' => 'Student Name'],
            ['key' => 'guardian', 'label' => 'Guardian Name'], ['key' => 'admission_date', 'label' => 'Admission Date'],
            ['key' => 'class', 'label' => 'Class'], ['key' => 'section', 'label' => 'Section'],
            ['key' => 'status', 'label' => 'Enrollment Status', 'type' => 'status'],
        ], $rows);
        $result['stats'] = [
            ['label' => 'Students', 'value' => $rows->count(), 'tone' => 'primary'],
            ['label' => 'Active', 'value' => $rows->where('status', 'active')->count(), 'tone' => 'success'],
            ['label' => 'Inactive / struck off', 'value' => $rows->where('status', '!=', 'active')->count(), 'tone' => 'danger'],
        ];
        $result['notes'] = ['Historical class and section values use enrollment records when an academic session is selected.'];

        return $result;
    }

    private function examReport(Request $request, string $type): array
    {
        $examQuery = Exam::with(['schoolClass', 'subjects.subject', 'subjects.marks'])
            ->when($request->integer('class_id'), fn ($q, $id) => $q->where('school_class_id', $id))
            ->when($request->integer('academic_session_id'), fn ($q, $id) => $q->where('academic_session_id', $id));
        $exam = $request->integer('exam_id') ? $examQuery->whereKey($request->integer('exam_id'))->first() : $examQuery->latest('starts_on')->first();
        $period = $exam ? [
            'mode' => 'range', 'from' => $exam->starts_on->copy()->startOfDay(), 'to' => $exam->ends_on->copy()->endOfDay(),
            'label' => $exam->starts_on->format('d M Y').' – '.$exam->ends_on->format('d M Y'),
        ] : $this->period($request, $type);

        if (! $exam) {
            $result = $this->baseResult($type, self::REPORT_TYPES[$type], $period, [], collect());
            $result['notes'] = ['Select a class or create an exam before running this report.'];

            return $result;
        }

        $studentRequest = clone $request;
        $studentRequest->merge(['class_id' => $exam->school_class_id, 'academic_session_id' => $exam->academic_session_id]);
        $students = $this->studentQuery($studentRequest, true)->get();
        $selectedId = $request->integer('student_id') ?: null;
        if ($selectedId) {
            $students = $students->where('id', $selectedId)->values();
        }
        $subjects = $exam->subjects;
        $passingPercentage = (float) SchoolSetting::value('passing_percentage', 40);

        $summaries = $students->map(function (Student $student) use ($subjects, $passingPercentage): array {
            $marks = $subjects->map(function ($subject) use ($student): array {
                $mark = $subject->marks->firstWhere('student_id', $student->id);

                return [
                    'subject' => $subject->subject?->name ?: 'Subject',
                    'max' => (float) $subject->max_marks,
                    'pass' => (float) $subject->pass_marks,
                    'obtained' => $mark && ! $mark->is_absent ? (float) $mark->marks_obtained : null,
                    'absent' => (bool) ($mark?->is_absent),
                    'marked' => (bool) $mark,
                ];
            });
            $max = (float) $marks->sum('max');
            $obtained = (float) $marks->sum(fn ($mark) => $mark['obtained'] ?? 0);
            $complete = $marks->isNotEmpty() && $marks->every(fn ($mark) => $mark['marked']);
            $percentage = $max > 0 ? round(($obtained / $max) * 100, 1) : null;
            $passed = $complete && $marks->every(fn ($mark) => ! $mark['absent'] && $mark['obtained'] >= $mark['pass']);

            return [
                'student_model' => $student, 'subject_marks' => $marks,
                'roll_no' => $student->roll_no ?: '—', 'student' => $student->name,
                'total_marks' => $max, 'obtained_marks' => $obtained,
                'percentage' => $percentage, 'grade' => $complete ? $this->grade($percentage ?? 0, $passingPercentage) : '—',
                'status' => ! $complete ? 'not marked' : ($passed ? 'pass' : 'fail'),
            ];
        });

        if ($selectedId && $summaries->isNotEmpty()) {
            $summary = $summaries->first();
            $rows = $summary['subject_marks']->map(fn ($mark) => [
                'subject' => $mark['subject'], 'total_marks' => $mark['max'], 'pass_marks' => $mark['pass'],
                'obtained_marks' => $mark['absent'] ? 'Absent' : $mark['obtained'],
                'status' => ! $mark['marked'] ? 'not marked' : ((! $mark['absent'] && $mark['obtained'] >= $mark['pass']) ? 'pass' : 'fail'),
            ]);
            $columns = [
                ['key' => 'subject', 'label' => 'Subject'], ['key' => 'total_marks', 'label' => 'Total Marks'],
                ['key' => 'pass_marks', 'label' => 'Pass Marks'], ['key' => 'obtained_marks', 'label' => 'Obtained Marks'],
                ['key' => 'status', 'label' => 'Result', 'type' => 'status'],
            ];
            $title = 'Academic Performance — '.$summary['student'];
        } else {
            $rows = $summaries->map(fn ($row) => collect($row)->except(['student_model', 'subject_marks'])->all());
            $columns = [
                ['key' => 'roll_no', 'label' => 'Roll Number'], ['key' => 'student', 'label' => 'Student Name'],
                ['key' => 'total_marks', 'label' => 'Total Marks'], ['key' => 'obtained_marks', 'label' => 'Obtained Marks'],
                ['key' => 'percentage', 'label' => 'Percentage', 'type' => 'percentage'],
                ['key' => 'grade', 'label' => 'Grade'], ['key' => 'status', 'label' => 'Result Status', 'type' => 'status'],
            ];
            $title = self::REPORT_TYPES[$type];
        }

        $result = $this->baseResult($type, $title, $period, $columns, $rows);
        $marked = $summaries->where('status', '!=', 'not marked');
        $result['stats'] = [
            ['label' => 'Exam', 'value' => $exam->name, 'tone' => 'primary'],
            ['label' => 'Students', 'value' => $summaries->count(), 'tone' => 'info'],
            ['label' => 'Passed', 'value' => $summaries->where('status', 'pass')->count(), 'tone' => 'success'],
            ['label' => 'Failed', 'value' => $summaries->where('status', 'fail')->count(), 'tone' => 'danger'],
            ['label' => 'Class average', 'value' => $marked->isNotEmpty() ? round($marked->avg('percentage'), 1).'%' : '—', 'tone' => 'warning'],
        ];
        $result['context'] = ['Class' => $exam->schoolClass?->name, 'Exam' => $exam->name, 'Status' => ucfirst($exam->status)];

        return $result;
    }

    private function familyReport(Request $request, array $period): array
    {
        $students = $this->studentQuery($request, true)->whereNotNull('family_id')->get();
        if ($request->integer('family_id')) {
            $students = $students->where('family_id', $request->integer('family_id'))->values();
        }
        $studentIds = $students->pluck('id');
        $families = Family::with('primaryGuardian')->whereIn('id', $students->pluck('family_id')->unique())->orderBy('family_code')->get();
        $invoices = FeeInvoice::whereIn('student_id', $studentIds)->where('status', '!=', 'cancelled')
            ->whereDate('billing_month', '>=', $period['from']->copy()->startOfMonth()->toDateString())
            ->whereDate('billing_month', '<=', $period['to']->copy()->endOfMonth()->toDateString())->get();
        $payments = Payment::completed()->whereIn('fee_invoice_id', $invoices->pluck('id'))
            ->whereDate('payment_date', '<=', $period['to']->toDateString())->get()->groupBy('fee_invoice_id');
        $previousInvoices = $period['mode'] === 'all' ? collect() : FeeInvoice::whereIn('student_id', $studentIds)
            ->where('status', '!=', 'cancelled')->whereDate('billing_month', '<', $period['from']->copy()->startOfMonth()->toDateString())->get();
        $previousPayments = Payment::completed()->whereIn('fee_invoice_id', $previousInvoices->pluck('id'))
            ->whereDate('payment_date', '<=', $period['to']->toDateString())->get()->groupBy('fee_invoice_id');
        $cash = Payment::completed()->whereIn('student_id', $studentIds)
            ->whereBetween('payment_date', [$period['from']->toDateString(), $period['to']->toDateString()])->get()->groupBy('student_id');

        $rows = $families->map(function (Family $family) use ($students, $invoices, $payments, $previousInvoices, $previousPayments, $cash): array {
            $familyStudents = $students->where('family_id', $family->id);
            $ids = $familyStudents->pluck('id');
            $current = $invoices->whereIn('student_id', $ids);
            $due = (float) $current->sum('total');
            $paid = (float) $current->sum(fn ($invoice) => ($payments[$invoice->id] ?? collect())->sum('amount'));
            $previous = $previousInvoices->whereIn('student_id', $ids);
            $previousDue = (float) $previous->sum('total');
            $previousPaid = (float) $previous->sum(fn ($invoice) => ($previousPayments[$invoice->id] ?? collect())->sum('amount'));
            $outstanding = max($due - $paid, 0);
            $previousOutstanding = max($previousDue - $previousPaid, 0);
            $cashReceived = (float) $ids->sum(fn ($id) => ($cash[$id] ?? collect())->sum('amount'));

            return [
                'family_id' => $family->family_code, 'guardian' => $family->primaryGuardian?->name ?: '—',
                'children' => $familyStudents->count(), 'monthly_fee' => $due, 'paid' => $paid,
                'outstanding' => $outstanding, 'previous_dues' => $previousOutstanding,
                'total_outstanding' => $outstanding + $previousOutstanding, 'cash_received' => $cashReceived,
                'status' => $outstanding <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            ];
        });
        $status = $request->string('status')->toString();
        if (in_array($status, ['paid', 'partial', 'unpaid'], true)) {
            $rows = $rows->where('status', $status)->values();
        }

        $result = $this->baseResult('family_fees', self::REPORT_TYPES['family_fees'], $period, [
            ['key' => 'family_id', 'label' => 'Family ID'], ['key' => 'guardian', 'label' => 'Guardian'],
            ['key' => 'children', 'label' => 'Children'], ['key' => 'monthly_fee', 'label' => 'Period Fees', 'type' => 'money'],
            ['key' => 'paid', 'label' => 'Applied', 'type' => 'money'], ['key' => 'outstanding', 'label' => 'Period Outstanding', 'type' => 'money'],
            ['key' => 'previous_dues', 'label' => 'Previous Dues', 'type' => 'money'],
            ['key' => 'total_outstanding', 'label' => 'Total Outstanding', 'type' => 'money'],
            ['key' => 'cash_received', 'label' => 'Cash Received', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ], $rows);
        $result['stats'] = [
            ['label' => 'Families', 'value' => $rows->count(), 'tone' => 'primary'],
            ['label' => 'Billing-period fees', 'value' => $this->money($rows->sum('monthly_fee')), 'tone' => 'warning'],
            ['label' => 'Applied to invoices', 'value' => $this->money($rows->sum('paid')), 'tone' => 'success'],
            ['label' => 'Cash received', 'value' => $this->money($rows->sum('cash_received')), 'tone' => 'info'],
            ['label' => 'Total outstanding', 'value' => $this->money($rows->sum('total_outstanding')), 'tone' => 'danger'],
        ];
        $result['notes'] = ['Family receipts are not added a second time; collections come only from completed student-ledger payments.'];

        return $result;
    }

    private function salaryReport(Request $request, array $period): array
    {
        abort_unless(($request->user()?->role ?: 'admin') === 'admin', 403, 'Payroll reports are restricted to administrators.');
        $records = SalaryRecord::with('staff')->whereDate('salary_month', $period['from']->copy()->startOfMonth()->toDateString())
            ->when($request->integer('staff_id'), fn ($q, $id) => $q->where('staff_id', $id))
            ->when(in_array($request->input('status'), ['paid', 'unpaid'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('staff_id')->get();
        $rows = $records->map(fn (SalaryRecord $record) => [
            'employee_no' => $record->staff?->employee_no ?: '—', 'staff' => $record->staff?->name ?: '—',
            'designation' => ucfirst($record->staff?->role ?: '—'), 'basic' => (float) $record->basic_salary,
            'allowances' => (float) $record->allowances, 'deductions' => (float) $record->deductions,
            'net' => (float) $record->net_salary, 'status' => $record->status,
        ]);
        $result = $this->baseResult('staff_salary', self::REPORT_TYPES['staff_salary'], $period, [
            ['key' => 'employee_no', 'label' => 'Employee No'], ['key' => 'staff', 'label' => 'Staff Member'],
            ['key' => 'designation', 'label' => 'Designation'], ['key' => 'basic', 'label' => 'Basic Salary', 'type' => 'money'],
            ['key' => 'allowances', 'label' => 'Allowances', 'type' => 'money'], ['key' => 'deductions', 'label' => 'Deductions', 'type' => 'money'],
            ['key' => 'net', 'label' => 'Net Salary', 'type' => 'money'], ['key' => 'status', 'label' => 'Payment Status', 'type' => 'status'],
        ], $rows);
        $result['stats'] = [
            ['label' => 'Payroll records', 'value' => $rows->count(), 'tone' => 'primary'],
            ['label' => 'Net payable', 'value' => $this->money($rows->sum('net')), 'tone' => 'warning'],
            ['label' => 'Paid', 'value' => $this->money($rows->where('status', 'paid')->sum('net')), 'tone' => 'success'],
            ['label' => 'Unpaid', 'value' => $this->money($rows->where('status', 'unpaid')->sum('net')), 'tone' => 'danger'],
        ];

        return $result;
    }

    private function timetableReport(Request $request): array
    {
        $entries = TimetableEntry::with(['schoolClass', 'section', 'subject', 'staff'])
            ->when($request->integer('class_id'), fn ($q, $id) => $q->where('school_class_id', $id))
            ->when($request->integer('section_id'), fn ($q, $id) => $q->where('section_id', $id))
            ->orderBy('day_of_week')->orderBy('starts_at')->get();
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $rows = $entries->map(fn (TimetableEntry $entry) => [
            'day' => $days[$entry->day_of_week] ?? 'Day '.$entry->day_of_week,
            'time' => Carbon::parse($entry->starts_at)->format('g:i A').' – '.Carbon::parse($entry->ends_at)->format('g:i A'),
            'class' => $entry->schoolClass?->name ?: '—', 'section' => $entry->section?->name ?: 'All',
            'subject' => $entry->subject?->name ?: '—', 'teacher' => $entry->staff?->name ?: '—', 'room' => $entry->room ?: '—',
        ]);
        $period = ['mode' => 'all', 'from' => now()->startOfDay(), 'to' => now()->endOfDay(), 'label' => 'Current timetable'];
        $result = $this->baseResult('timetable', self::REPORT_TYPES['timetable'], $period, [
            ['key' => 'day', 'label' => 'Day'], ['key' => 'time', 'label' => 'Time'],
            ['key' => 'class', 'label' => 'Class'], ['key' => 'section', 'label' => 'Section'],
            ['key' => 'subject', 'label' => 'Subject'], ['key' => 'teacher', 'label' => 'Teacher'], ['key' => 'room', 'label' => 'Room'],
        ], $rows);
        $result['stats'] = [
            ['label' => 'Periods', 'value' => $rows->count(), 'tone' => 'primary'],
            ['label' => 'Teaching days', 'value' => $rows->pluck('day')->unique()->count(), 'tone' => 'info'],
            ['label' => 'Subjects', 'value' => $rows->pluck('subject')->unique()->count(), 'tone' => 'success'],
        ];

        return $result;
    }

    private function studentQuery(Request $request, bool $withRelations): Builder
    {
        $sessionId = $request->integer('academic_session_id') ?: null;
        $classId = $request->integer('class_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $query = Student::query();

        if ($sessionId) {
            $query->whereHas('enrollments', function ($enrollment) use ($sessionId, $classId, $sectionId): void {
                $enrollment->where('academic_session_id', $sessionId)
                    ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
                    ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId));
            });
        } else {
            $query->when($classId, fn ($q) => $q->where('school_class_id', $classId))
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId));
        }

        $query->when($request->integer('student_id'), fn ($q, $id) => $q->whereKey($id));
        if ($withRelations) {
            $query->with(['schoolClass', 'section', 'guardian', 'enrollments.schoolClass', 'enrollments.section']);
        }

        return $query->orderBy('roll_no')->orderBy('name');
    }

    private function applyStudentFiltersToRelation(Builder $query, Request $request): void
    {
        $query->when($request->integer('student_id'), fn ($q, $id) => $q->where('student_id', $id));
        $classId = $request->integer('class_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $sessionId = $request->integer('academic_session_id') ?: null;
        if ($classId || $sectionId || $sessionId) {
            $query->whereHas('student', function ($student) use ($classId, $sectionId, $sessionId): void {
                if ($sessionId) {
                    $student->whereHas('enrollments', function ($enrollment) use ($sessionId, $classId, $sectionId): void {
                        $enrollment->where('academic_session_id', $sessionId)
                            ->when($classId, fn ($q) => $q->where('school_class_id', $classId))
                            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId));
                    });
                } else {
                    $student->when($classId, fn ($q) => $q->where('school_class_id', $classId))
                        ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId));
                }
            });
        }
    }

    private function studentClass(Student $student, ?int $sessionId): string
    {
        $enrollment = $sessionId ? $student->enrollments->firstWhere('academic_session_id', $sessionId) : null;

        return $enrollment?->schoolClass?->name ?: $student->schoolClass?->name ?: $student->class ?: '—';
    }

    private function studentSection(Student $student, ?int $sessionId): string
    {
        $enrollment = $sessionId ? $student->enrollments->firstWhere('academic_session_id', $sessionId) : null;

        return $enrollment?->section?->name ?: $student->section?->name ?: '—';
    }

    private function eligibleFrom(Student $student, Carbon $periodStart, ?int $sessionId): Carbon
    {
        $eligible = $periodStart->copy();
        if ($student->admission_date && $student->admission_date->gt($eligible)) {
            $eligible = $student->admission_date->copy();
        }
        if ($sessionId) {
            $enrolledOn = $student->enrollments->firstWhere('academic_session_id', $sessionId)?->enrolled_on;
            if ($enrolledOn && $enrolledOn->gt($eligible)) {
                $eligible = $enrolledOn->copy();
            }
        }

        return $eligible;
    }

    private function period(Request $request, string $type): array
    {
        $mode = $request->input('period_mode', 'monthly');
        if (in_array($type, ['monthly_attendance', 'staff_salary'], true)) {
            $mode = 'monthly';
        }
        if (in_array($type, ['exams', 'student_performance', 'timetable'], true)) {
            $mode = 'all';
        }
        if ($mode === 'monthly') {
            $from = Carbon::parse($request->input('month', now()->format('Y-m')).'-01')->startOfMonth();
            $to = $from->copy()->endOfMonth();

            return compact('mode', 'from', 'to') + ['label' => $from->format('F Y')];
        }
        if ($mode === 'all') {
            $from = Carbon::create(1900, 1, 1)->startOfDay();
            $to = now()->endOfDay();

            return compact('mode', 'from', 'to') + ['label' => 'All available records'];
        }

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        return compact('mode', 'from', 'to') + ['label' => $from->format('d M Y').' – '.$to->format('d M Y')];
    }

    private function baseResult(string $type, string $title, array $period, array $columns, Collection $rows): array
    {
        return [
            'type' => $type, 'title' => $title, 'period' => $period,
            'columns' => $columns, 'rows' => $rows, 'stats' => [], 'context' => [], 'notes' => [],
            'orientation' => count($columns) > 6 ? 'landscape' : 'portrait', 'signatures' => false,
            'empty' => 'No records match the selected report filters.',
        ];
    }

    private function grade(float $percentage, float $passing): string
    {
        return match (true) {
            $percentage >= 80 => 'A+', $percentage >= 70 => 'A', $percentage >= 60 => 'B',
            $percentage >= 50 => 'C', $percentage >= $passing => 'D', default => 'F',
        };
    }

    private function money(float|int $amount): string
    {
        return 'Rs '.number_format((float) $amount);
    }
}
