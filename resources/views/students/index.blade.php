@extends('layouts.dashboard')

@section('title', 'Students')
@section('eyebrow', 'Student records')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-end gap-3">
        <div>
            <span class="page-kicker">Student directory</span>
            <h1 class="page-title">Every student, one clear record.</h1>
            <p class="page-subtitle">Search, update, and open fee histories without the clutter.</p>
        </div>
        <div class="d-flex gap-2"><a href="{{ route('students.promotion-form') }}" class="btn btn-outline-primary"><i class="fa-solid fa-arrow-up-right-dots me-2"></i>Promote class</a><a href="{{ route('students.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus me-2"></i>Add student</a></div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="mini-metrics">
    <div class="mini-metric"><strong>{{ number_format($studentStats['total']) }}</strong><span>Total</span></div>
    <div class="mini-metric"><strong>{{ number_format($studentStats['active']) }}</strong><span>Active</span></div>
    <div class="mini-metric"><strong>{{ number_format($studentStats['campus_1']) }}</strong><span>Campus 1</span></div>
    <div class="mini-metric"><strong>{{ number_format($studentStats['campus_2']) }}</strong><span>Campus 2</span></div>
</div>

<div class="dashboard-card filter-panel">
    <div class="card-body">
        <form method="GET" action="{{ route('students.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="search" class="form-label">Search students</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, parent, class or phone">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label for="class" class="form-label">Class</label>
                <select class="form-select" id="class" name="class">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class }}" @selected(request('class') == $class)>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="campus" class="form-label">Campus</label>
                <select class="form-select" id="campus" name="campus">
                    <option value="">All campuses</option>
                    <option value="campus_1" @selected(request('campus') == 'campus_1')>Campus 1</option>
                    <option value="campus_2" @selected(request('campus') == 'campus_2')>Campus 2</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status') == 'active')>Active</option>
                    <option value="inactive" @selected(request('status') == 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary" aria-label="Clear filters"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-address-book me-2"></i>Student directory</h2>
            <small class="text-muted">{{ $students->total() }} {{ Str::plural('record', $students->total()) }}</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th class="d-none d-md-table-cell">Campus</th>
                    <th class="d-none d-xl-table-cell">Monthly fee</th>
                    <th class="d-none d-lg-table-cell">Contact</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>
                            <div class="student-identity">
                                <span class="student-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $student->name }}</strong>
                                    <small>{{ $student->father_name }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="badge bg-info">{{ $student->class }}</span></td>
                        <td class="d-none d-md-table-cell">{{ ucfirst(str_replace('_', ' ', $student->campus)) }}</td>
                        <td class="d-none d-xl-table-cell"><strong>Rs {{ number_format($student->monthly_fee, 0) }}</strong></td>
                        <td class="d-none d-lg-table-cell">{{ $student->mobile ?: ($student->contact ?: '—') }}</td>
                        <td><span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span></td>
                        <td>
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('students.show', $student) }}" class="btn btn-outline-primary btn-sm" title="Open student"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-outline-success btn-sm" title="Record fee"><i class="fa-solid fa-wallet"></i></a>
                                <a href="{{ route('students.edit', $student) }}" class="btn btn-outline-secondary btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <span class="empty-state-icon"><i class="fa-solid fa-user-graduate"></i></span>
                                <h5>No students found</h5>
                                <p>Try clearing the filters or add the first student.</p>
                                <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm">Add student</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($students->hasPages())
        <div class="card-footer">{{ $students->links() }}</div>
    @endif
</div>
@endsection
