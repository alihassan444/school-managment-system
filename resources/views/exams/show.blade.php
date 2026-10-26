@extends('layouts.dashboard')
@section('title', $exam->name)
@section('eyebrow', $exam->schoolClass->name.' exam')
@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3"><div><span class="eyebrow">Date sheet &amp; marks</span><h1>{{ $exam->name }}</h1><p>{{ $exam->schoolClass->name }} · {{ $exam->starts_on->format('d M') }} to {{ $exam->ends_on->format('d M Y') }}</p></div><a href="{{ route('exams.index') }}" class="btn btn-outline-primary">All exams</a></div>
@include('partials.flash')
<div class="surface-card mb-4"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-calendar-plus"></i>Add date-sheet subject</h2></div><div class="card-body"><form method="POST" action="{{ route('exams.subjects.store',$exam) }}" class="row g-3 align-items-end">@csrf
<div class="col-md-3"><label class="form-label">Subject</label><select class="form-select" id="examSubjectSelect" name="subject_id" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}" data-max-marks="{{ $subject->pivot->max_marks }}" data-pass-marks="{{ $subject->pivot->pass_marks }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">Date</label><input class="form-control" type="date" name="exam_date" value="{{ old('exam_date') }}" min="{{ $exam->starts_on->toDateString() }}" max="{{ $exam->ends_on->toDateString() }}" required></div><div class="col-md-2"><label class="form-label">Starts</label><input class="form-control" type="time" name="starts_at" value="{{ old('starts_at') }}"></div><div class="col-md-2"><label class="form-label">Ends</label><input class="form-control" type="time" name="ends_at" value="{{ old('ends_at') }}"></div><div class="col"><label class="form-label">Total</label><input class="form-control" id="examMaxMarks" type="number" name="max_marks" readonly aria-readonly="true"></div><div class="col"><label class="form-label">Pass</label><input class="form-control" id="examPassMarks" type="number" name="pass_marks" readonly aria-readonly="true"></div><div class="col-auto"><button class="btn btn-primary" @disabled($subjects->isEmpty())><i class="fa-solid fa-plus"></i></button></div>
</form></div></div>
<div class="surface-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Subject</th><th>Date</th><th>Time</th><th>Marks</th><th></th></tr></thead><tbody>@forelse($exam->subjects as $paper)<tr><td><strong>{{ $paper->subject->name }}</strong></td><td>{{ $paper->exam_date->format('D, d M Y') }}</td><td>{{ $paper->starts_at ? substr($paper->starts_at,0,5) : '—' }} – {{ $paper->ends_at ? substr($paper->ends_at,0,5) : '—' }}</td><td>{{ $paper->pass_marks }} / {{ $paper->max_marks }} pass</td><td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('exams.marks',$paper) }}">Enter marks</a></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><h5>No date sheet yet</h5><p>Assign subjects to the class first, then add papers above.</p></div></td></tr>@endforelse</tbody></table></div></div>
<div class="surface-card mt-4"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-user-graduate"></i>Student documents</h2><span class="text-muted">{{ $students->count() }} students</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Roll no</th><th>Student</th><th class="text-end">Documents</th></tr></thead><tbody>@forelse($students as $student)<tr><td>{{ $student->roll_no ?: '—' }}</td><td><strong>{{ $student->name }}</strong><small class="d-block text-muted">{{ $student->admission_no }}</small></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('exams.roll-slip',[$exam,$student]) }}">Roll slip</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('exams.result-card',[$exam,$student]) }}">Result card</a></td></tr>@empty<tr><td colspan="3" class="text-muted">No students assigned to this class.</td></tr>@endforelse</tbody></table></div></div>
@endsection

@push('scripts')
<script>
(() => {
    const subject = document.getElementById('examSubjectSelect');
    const total = document.getElementById('examMaxMarks');
    const passing = document.getElementById('examPassMarks');

    if (!subject || !total || !passing) return;

    const syncClassMarks = () => {
        const selected = subject.options[subject.selectedIndex];
        total.value = selected?.dataset.maxMarks ?? '';
        passing.value = selected?.dataset.passMarks ?? '';
    };

    subject.addEventListener('change', syncClassMarks);
    syncClassMarks();
})();
</script>
@endpush
