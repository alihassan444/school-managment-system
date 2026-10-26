@extends('layouts.dashboard')
@section('title', 'Attendance')
@section('eyebrow', 'Daily register')
@section('content')
<div class="page-header"><span class="eyebrow">Daily operations</span><h1>Student attendance</h1><p>Select a date and class, then save the complete register in one action.</p></div>
@include('partials.flash')
<div class="surface-card mb-4"><div class="card-body"><form method="GET" class="row g-3 align-items-end">
    <div class="col-md-4"><label class="form-label">Attendance date</label><input class="form-control" type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}" required></div>
    <div class="col-md-5"><label class="form-label">Class</label><select class="form-select" name="class_id" required><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><button class="btn btn-primary w-100">Open register</button></div>
</form></div></div>
@if($classId)
<div class="stats-grid mb-4">
    @foreach([['Present',$summary['present'],'success','fa-user-check'],['Absent',$summary['absent'],'danger','fa-user-xmark'],['Leave',$summary['leave'],'warning','fa-calendar-minus'],['Unmarked',$summary['unmarked'],'primary','fa-circle-question']] as [$label,$value,$tone,$icon])
    <article class="stat-card tone-{{ $tone }}"><span class="stat-icon"><i class="fa-solid {{ $icon }}"></i></span><div class="stat-value">{{ $value }}</div><div class="stat-label">{{ $label }}</div></article>
    @endforeach
</div>
<form method="POST" action="{{ route('attendance.store') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="class_id" value="{{ $classId }}">
<div class="surface-card"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="card-title"><i class="fa-solid fa-clipboard-check"></i>Register for {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}</h2><button class="btn btn-primary" @disabled($students->isEmpty())>Save attendance</button></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Roll no</th><th>Student</th><th>Section</th><th>Status</th></tr></thead><tbody>
@forelse($students as $student) @php($current = $records->get($student->id)?->status ?? 'present')
<tr><td>{{ $student->roll_no ?: '—' }}</td><td><strong>{{ $student->name }}</strong><small class="d-block text-muted">{{ $student->admission_no ?: 'Legacy record' }}</small></td><td>{{ $student->section?->name ?: '—' }}</td><td><div class="attendance-options">@foreach(['present'=>'Present','absent'=>'Absent','leave'=>'Leave','late'=>'Late'] as $value=>$label)<label><input type="radio" name="attendance[{{ $student->id }}]" value="{{ $value }}" @checked($current === $value)><span>{{ $label }}</span></label>@endforeach</div></td></tr>
@empty<tr><td colspan="4"><div class="empty-state"><h5>No enrolled students</h5><p>Assign students to this structured class from their student record.</p></div></td></tr>@endforelse
</tbody></table></div></div></form>
@endif
@endsection
