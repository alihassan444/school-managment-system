@extends('layouts.dashboard')

@section('title', 'Classes & Subjects')
@section('eyebrow', 'Academic structure')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3">
    <div><span class="eyebrow">Academic setup</span><h1>Classes, sections &amp; subjects</h1><p>Build the teaching structure used by admissions, attendance, exams and timetables.</p></div>
</div>
@include('partials.flash')

<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-school"></i>New class</h2></div><div class="card-body">
            <form method="POST" action="{{ route('academics.classes.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Class name</label><input class="form-control" name="name" value="{{ old('name') }}" required placeholder="Grade 6"></div>
                <div class="row g-3"><div class="col-6"><label class="form-label">Order</label><input class="form-control" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"></div><div class="col-6"><label class="form-label">Monthly fee</label><input class="form-control" type="number" name="monthly_fee" value="{{ old('monthly_fee', 0) }}" min="0" step="0.01" required></div></div>
                <button class="btn btn-primary w-100 mt-3"><i class="fa-solid fa-plus me-2"></i>Add class</button>
            </form>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-users-rectangle"></i>New section</h2></div><div class="card-body">
            <form method="POST" action="{{ route('academics.sections.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Class</label><select class="form-select" name="school_class_id" required><option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                <div class="row g-3"><div class="col-6"><label class="form-label">Section</label><input class="form-control" name="name" required placeholder="A"></div><div class="col-6"><label class="form-label">Capacity</label><input class="form-control" type="number" name="capacity" min="1"></div></div>
                <div class="mt-3"><label class="form-label">Class teacher</label><select class="form-select" name="class_teacher_id"><option value="">Not assigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                <button class="btn btn-primary w-100 mt-3">Add section</button>
            </form>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-book-open"></i>New subject</h2></div><div class="card-body">
            <form method="POST" action="{{ route('academics.subjects.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Subject name</label><input class="form-control" name="name" required placeholder="Mathematics"></div>
                <div class="mb-3"><label class="form-label">Code</label><input class="form-control" name="code" required placeholder="MATH"></div>
                <button class="btn btn-primary w-100">Add subject</button>
            </form>
        </div></div>
    </div>
</div>

<div class="surface-card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="card-title"><i class="fa-solid fa-link"></i>Assign subject to class</h2><span class="badge text-bg-light">{{ $subjects->count() }} subjects</span></div><div class="card-body">
    <form method="POST" action="{{ route('academics.assign-subject') }}" class="row g-3 align-items-end">@csrf
        <div class="col-md-3"><label class="form-label">Class</label><select class="form-select" name="school_class_id" required>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Subject</label><select class="form-select" name="subject_id" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Teacher</label><select class="form-select" name="teacher_id"><option value="">Not assigned</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
        <div class="col-6 col-md-1"><label class="form-label">Total</label><input class="form-control" type="number" name="max_marks" value="100" required></div>
        <div class="col-6 col-md-1"><label class="form-label">Pass</label><input class="form-control" type="number" name="pass_marks" value="40" required></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fa-solid fa-check"></i></button></div>
    </form>
</div></div>

<div class="surface-card"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-sitemap"></i>Academic structure</h2></div><div class="card-body p-0">
    @forelse($classes as $class)
        <div class="academic-class-row">
            <div><strong>{{ $class->name }}</strong><small>PKR {{ number_format($class->monthly_fee) }}/month</small></div>
            <div class="d-flex flex-wrap gap-2">@forelse($class->sections as $section)<span class="badge rounded-pill text-bg-light">Section {{ $section->name }}@if($section->classTeacher) · {{ $section->classTeacher->name }}@endif</span>@empty<span class="text-muted small">No sections</span>@endforelse</div>
            <div class="d-flex flex-wrap gap-2">@forelse($class->subjects as $subject)<span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $subject->name }}</span>@empty<span class="text-muted small">No subjects assigned</span>@endforelse</div>
        </div>
    @empty<div class="empty-state"><div class="empty-state-icon"><i class="fa-solid fa-school"></i></div><h5>No classes yet</h5><p>Add the first class above.</p></div>@endforelse
</div></div>
@endsection
