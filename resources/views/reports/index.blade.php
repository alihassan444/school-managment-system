@extends('layouts.dashboard')
@section('title', 'Reports')
@section('eyebrow', 'Operational intelligence')
@section('content')
@php
    $activeType = $selected['report_type'] ?? 'fee_collection';
    $periodMode = $selected['period_mode'] ?? 'monthly';
    $activeMonth = \Illuminate\Support\Carbon::parse(($selected['month'] ?? now()->format('Y-m')).'-01');
    $previousMonthQuery = array_merge(request()->query(), ['month' => $activeMonth->copy()->subMonth()->format('Y-m'), 'period_mode' => 'monthly']);
    $nextMonthQuery = array_merge(request()->query(), ['month' => $activeMonth->copy()->addMonth()->format('Y-m'), 'period_mode' => 'monthly']);
@endphp

<div class="page-header d-flex justify-content-between align-items-start gap-3">
    <div>
        <span class="eyebrow">Flexible reporting</span>
        <h1>School reports</h1>
        <p>Select a report, narrow its scope, then preview or export the exact same records.</p>
    </div>
    <div class="report-actions no-print">
        <a href="{{ route('reports.print', request()->query()) }}" target="_blank" class="btn btn-outline-primary"><i class="fa-solid fa-print me-2"></i>Print</a>
        <a href="{{ route('reports.csv', request()->query()) }}" class="btn btn-outline-primary"><i class="fa-solid fa-file-csv me-2"></i>CSV</a>
        <a href="{{ route('reports.pdf', request()->query()) }}" class="btn btn-primary"><i class="fa-solid fa-file-pdf me-2"></i>PDF</a>
    </div>
</div>

@include('partials.flash')

<div class="surface-card report-filter-card mb-4 no-print">
    <div class="card-header">
        <div><h2 class="card-title"><i class="fa-solid fa-sliders"></i>Report filters</h2><small class="text-muted">Only filters relevant to the selected report are shown.</small></div>
    </div>
    <div class="card-body">
        <form method="GET" id="reportFilterForm" class="row g-3 align-items-end">
            <div class="col-xl-3 col-md-6">
                <label class="form-label" for="reportType">Report Type</label>
                <select class="form-select" name="report_type" id="reportType">
                    @foreach($reportTypes as $value => $label)
                        <option value="{{ $value }}" @selected($activeType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="class">
                <label class="form-label" for="reportClass">Class / Grade</label>
                <select class="form-select" name="class_id" id="reportClass">
                    <option value="">Whole school</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(($selected['class_id'] ?? null) == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="section">
                <label class="form-label" for="reportSection">Section</label>
                <select class="form-select" name="section_id" id="reportSection">
                    <option value="">All sections</option>
                    @foreach($classes as $class)
                        @foreach($class->sections as $section)
                            <option value="{{ $section->id }}" data-class="{{ $class->id }}" @selected(($selected['section_id'] ?? null) == $section->id)>{{ $class->name }} · {{ $section->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-md-6" data-report-filter="student">
                <label class="form-label" for="reportStudent">Student</label>
                <select class="form-select" name="student_id" id="reportStudent" data-selected="{{ $selected['student_id'] ?? '' }}">
                    <option value="">All Students</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(($selected['student_id'] ?? null) == $student->id)>{{ $student->name }}{{ $student->roll_no ? ' · '.$student->roll_no : '' }}</option>
                    @endforeach
                </select>
                <small class="form-text" id="studentLoading" hidden>Loading students…</small>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="session">
                <label class="form-label" for="reportSession">Academic Session</label>
                <select class="form-select" name="academic_session_id" id="reportSession">
                    <option value="">Current assignment</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" @selected(($selected['academic_session_id'] ?? null) == $session->id)>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-md-6" data-report-filter="exam">
                <label class="form-label" for="reportExam">Exam</label>
                <select class="form-select" name="exam_id" id="reportExam">
                    <option value="">Latest matching exam</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}" data-class="{{ $exam->school_class_id }}" data-session="{{ $exam->academic_session_id }}" @selected(($selected['exam_id'] ?? null) == $exam->id)>{{ $exam->name }} · {{ $exam->schoolClass?->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-md-6" data-report-filter="family">
                <label class="form-label" for="reportFamily">Family</label>
                <select class="form-select" name="family_id" id="reportFamily">
                    <option value="">All families</option>
                    @foreach($families as $family)
                        <option value="{{ $family->id }}" @selected(($selected['family_id'] ?? null) == $family->id)>{{ $family->family_code }} · {{ $family->primaryGuardian?->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-3 col-md-6" data-report-filter="staff">
                <label class="form-label" for="reportStaff">Staff Member</label>
                <select class="form-select" name="staff_id" id="reportStaff">
                    <option value="">All staff</option>
                    @foreach($staffMembers as $member)
                        <option value="{{ $member->id }}" @selected(($selected['staff_id'] ?? null) == $member->id)>{{ $member->name }} · {{ ucfirst($member->role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="status">
                <label class="form-label" for="reportStatus">Status</label>
                <select class="form-select" name="status" id="reportStatus">
                    <option value="">All statuses</option>
                    @foreach(['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'] as $value => $label)
                        <option value="{{ $value }}" @selected(($selected['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="period">
                <label class="form-label" for="periodMode">Reporting Period</label>
                <select class="form-select" name="period_mode" id="periodMode">
                    <option value="all" @selected($periodMode === 'all')>All available dates</option>
                    <option value="monthly" @selected($periodMode === 'monthly')>Monthly</option>
                    <option value="range" @selected($periodMode === 'range')>Custom date range</option>
                </select>
            </div>

            <div class="col-xl-3 col-md-6" data-report-filter="month" data-period-field="month">
                <label class="form-label" for="reportMonth">Reporting Month</label>
                <div class="report-month-control">
                    <a href="{{ route('reports.index', $previousMonthQuery) }}" class="btn btn-outline-primary" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></a>
                    <input class="form-control" type="month" name="month" id="reportMonth" value="{{ $selected['month'] ?? now()->format('Y-m') }}">
                    <a href="{{ route('reports.index', $nextMonthQuery) }}" class="btn btn-outline-primary" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="col-xl-2 col-md-6" data-report-filter="period" data-period-field="from">
                <label class="form-label" for="reportFrom">From Date</label>
                <input class="form-control" type="date" name="from" id="reportFrom" value="{{ $selected['from'] ?? now()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-xl-2 col-md-6" data-report-filter="period" data-period-field="to">
                <label class="form-label" for="reportTo">To Date</label>
                <input class="form-control" type="date" name="to" id="reportTo" value="{{ $selected['to'] ?? now()->toDateString() }}">
            </div>

            <div class="col-xl-2 col-md-6 ms-auto">
                <button class="btn btn-primary w-100" id="runReportButton"><i class="fa-solid fa-play me-2"></i><span>Run Report</span></button>
            </div>
        </form>
    </div>
</div>

<section class="report-preview" aria-labelledby="reportTitle">
    <div class="report-preview-heading">
        <div>
            <span class="eyebrow">{{ $schoolName }}</span>
            <h2 id="reportTitle">{{ $report['title'] }}</h2>
            <p>{{ $report['period']['label'] }}</p>
        </div>
        @if($report['context'])
            <div class="report-context">
                @foreach($report['context'] as $label => $value)<span><small>{{ $label }}</small><strong>{{ $value ?: '—' }}</strong></span>@endforeach
            </div>
        @endif
    </div>

    @if($report['stats'])
        <div class="stats-grid report-stats mb-4">
            @foreach($report['stats'] as $stat)
                <article class="stat-card tone-{{ $stat['tone'] ?? 'primary' }}"><div class="stat-value">{{ $stat['value'] }}</div><div class="stat-label">{{ $stat['label'] }}</div></article>
            @endforeach
        </div>
    @endif

    <div class="surface-card">
        <div class="card-header">
            <div><h3 class="card-title"><i class="fa-solid fa-table-list"></i>{{ $report['title'] }}</h3><small class="text-muted">{{ $report['rows']->count() }} matching {{ Str::plural('record', $report['rows']->count()) }}</small></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 report-table">
                <thead><tr>@foreach($report['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            @foreach($report['columns'] as $column)
                                @php($value = data_get($row, $column['key']))
                                <td>
                                    @if(($column['type'] ?? null) === 'money')
                                        <span class="financial-value">Rs {{ number_format((float) $value) }}</span>
                                    @elseif(($column['type'] ?? null) === 'percentage')
                                        {{ $value === null ? '—' : number_format((float) $value, 1).'%' }}
                                    @elseif(($column['type'] ?? null) === 'status')
                                        <span class="status-pill status-{{ Str::slug((string) $value) }}">{{ Str::headline((string) $value) }}</span>
                                    @else
                                        {{ $value === null || $value === '' ? '—' : $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(count($report['columns']), 1) }}"><div class="empty-state"><i class="fa-solid fa-folder-open"></i><h5>No records found</h5><p>{{ $report['empty'] }}</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($report['notes'])
        <div class="report-notes mt-3">@foreach($report['notes'] as $note)<p><i class="fa-solid fa-circle-info"></i>{{ $note }}</p>@endforeach</div>
    @endif
</section>
@endsection

@push('scripts')
<script>
(() => {
    const filterMap = @json($filterMap);
    const form = document.getElementById('reportFilterForm');
    const type = document.getElementById('reportType');
    const classSelect = document.getElementById('reportClass');
    const sectionSelect = document.getElementById('reportSection');
    const sessionSelect = document.getElementById('reportSession');
    const studentSelect = document.getElementById('reportStudent');
    const periodMode = document.getElementById('periodMode');
    const loading = document.getElementById('studentLoading');
    const studentEndpoint = @json(route('reports.students'));

    function updateFilters() {
        const allowed = new Set(filterMap[type.value] || []);
        document.querySelectorAll('[data-report-filter]').forEach((field) => {
            const visible = allowed.has(field.dataset.reportFilter);
            field.hidden = !visible;
            field.querySelectorAll('input,select').forEach((input) => input.disabled = !visible);
        });
        if (allowed.has('month')) {
            document.querySelectorAll('[data-period-field="month"]').forEach((field) => { field.hidden = false; field.querySelectorAll('input').forEach((input) => input.disabled = false); });
        }
        updatePeriodFields();
        filterDependentOptions();
    }

    function updatePeriodFields() {
        const allowed = new Set(filterMap[type.value] || []);
        const mode = periodMode && !periodMode.disabled ? periodMode.value : 'monthly';
        document.querySelectorAll('[data-period-field]').forEach((field) => {
            const name = field.dataset.periodField;
            const visible = name === 'month'
                ? (allowed.has('month') || (allowed.has('period') && mode === 'monthly'))
                : (allowed.has('period') && mode === 'range');
            field.hidden = !visible;
            field.querySelectorAll('input').forEach((input) => input.disabled = !visible);
        });
    }

    function filterDependentOptions() {
        const classId = classSelect?.value || '';
        [...sectionSelect.options].forEach((option, index) => option.hidden = index > 0 && !!classId && option.dataset.class !== classId);
        if (sectionSelect.selectedOptions[0]?.hidden) sectionSelect.value = '';
        const sessionId = sessionSelect?.value || '';
        document.querySelectorAll('#reportExam option[data-class]').forEach((option) => {
            option.hidden = (!!classId && option.dataset.class !== classId) || (!!sessionId && option.dataset.session !== sessionId);
        });
    }

    async function loadStudents() {
        if (!studentSelect || studentSelect.disabled) return;
        loading.hidden = false;
        studentSelect.disabled = true;
        const params = new URLSearchParams();
        if (classSelect.value) params.set('class_id', classSelect.value);
        if (sectionSelect.value) params.set('section_id', sectionSelect.value);
        if (sessionSelect.value) params.set('academic_session_id', sessionSelect.value);
        const previous = studentSelect.value;
        try {
            const response = await fetch(`${studentEndpoint}?${params.toString()}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Unable to load students');
            const students = await response.json();
            studentSelect.innerHTML = '<option value="">All Students</option>' + students.map((student) => `<option value="${student.id}">${student.name}${student.roll_no ? ' · ' + student.roll_no : ''}</option>`).join('');
            if ([...studentSelect.options].some((option) => option.value === previous)) studentSelect.value = previous;
        } finally {
            studentSelect.disabled = false;
            loading.hidden = true;
        }
    }

    type.addEventListener('change', updateFilters);
    periodMode?.addEventListener('change', updatePeriodFields);
    [classSelect, sectionSelect, sessionSelect].forEach((select) => select?.addEventListener('change', () => { filterDependentOptions(); loadStudents(); }));
    form.addEventListener('submit', () => {
        const button = document.getElementById('runReportButton');
        button.disabled = true;
        button.querySelector('span').textContent = 'Generating…';
    });
    updateFilters();
})();
</script>
@endpush
