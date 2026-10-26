@extends('layouts.dashboard')
@section('title', 'Result Card')
@section('content')
@php
    $total = $exam->subjects->sum('max_marks');
    $obtained = $exam->subjects->sum(fn($paper) => (float) optional($paper->marks->first())->marks_obtained);
    $percentage = $total > 0 ? round(($obtained/$total)*100, 1) : 0;
@endphp
<div class="page-header no-print d-flex justify-content-between"><div><span class="eyebrow">Result card</span><h1>{{ $student->name }}</h1></div><button class="btn btn-primary" onclick="window.print()">Print result card</button></div>
<div class="invoice-sheet"><div class="invoice-brand"><img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="Al Suffaa School crest"><div><h2>Al Suffaa School</h2><p>{{ $exam->name }} result card</p></div><div class="invoice-number"><small>CLASS</small><strong>{{ $exam->schoolClass->name }}</strong></div></div><div class="invoice-meta"><div><small>Student</small><strong>{{ $student->name }}</strong><span>{{ $student->admission_no ?: 'Student #'.$student->id }}</span></div><div><small>Father</small><strong>{{ $student->father_name }}</strong><span>{{ $student->roll_no ?: 'No roll number' }}</span></div><div><small>Result</small><strong>{{ $percentage }}%</strong><span>{{ $obtained }} / {{ $total }}</span></div></div><table class="table"><thead><tr><th>Subject</th><th>Total</th><th>Pass</th><th>Obtained</th><th>Result</th></tr></thead><tbody>@foreach($exam->subjects as $paper) @php($mark=$paper->marks->first())<tr><td>{{ $paper->subject->name }}</td><td>{{ $paper->max_marks }}</td><td>{{ $paper->pass_marks }}</td><td>{{ $mark?->is_absent ? 'Absent' : ($mark?->marks_obtained ?? '—') }}</td><td>{{ $mark && !$mark->is_absent && $mark->marks_obtained >= $paper->pass_marks ? 'Pass' : 'Needs attention' }}</td></tr>@endforeach</tbody></table></div>
@endsection
