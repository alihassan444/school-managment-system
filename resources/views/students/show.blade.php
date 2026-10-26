@extends('layouts.dashboard')

@section('title', 'Student profile')
@section('eyebrow', 'Student records')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-end gap-3">
        <div>
            <span class="page-kicker">{{ $student->admission_no ?: 'Student #'.str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</span>
            <h1 class="page-title">{{ $student->name }}</h1>
            <p class="page-subtitle">{{ $student->class }} · {{ ucfirst(str_replace('_', ' ', $student->campus)) }} · Joined {{ $student->created_at->format('M Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('students.edit', $student) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen me-2"></i>Edit</a>
            <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-primary"><i class="fa-solid fa-wallet me-2"></i>Record fee</a>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div>
        <article class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-id-card me-2"></i>Student information</h2>
                <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Full name</span><span class="info-value">{{ $student->name }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Father's name</span><span class="info-value">{{ $student->father_name }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Admission / roll no.</span><span class="info-value">{{ $student->admission_no ?: '—' }} / {{ $student->roll_no ?: '—' }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Date of birth / gender</span><span class="info-value">{{ $student->date_of_birth?->format('d M Y') ?: '—' }} / {{ ucfirst($student->gender ?: '—') }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Class</span><span class="info-value">{{ $student->class }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Session / section</span><span class="info-value">{{ $student->academicSession?->name ?: '—' }} / {{ $student->section?->name ?: '—' }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Campus</span><span class="info-value">{{ ucfirst(str_replace('_', ' ', $student->campus)) }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Monthly fee</span><span class="info-value">Rs {{ number_format($student->monthly_fee, 0) }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Contact</span><span class="info-value">{{ $student->contact ?: 'Not provided' }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Mobile</span><span class="info-value">{{ $student->mobile ?: 'Not provided' }}</span></div></div>
                    <div class="col-md-6"><div class="info-item"><span class="info-label">Guardian</span><span class="info-value">{{ $student->guardian?->name ?: $student->father_name }} · {{ $student->guardian?->phone ?: ($student->mobile ?: 'No phone') }}</span></div></div>
                    @if($student->address)
                        <div class="col-12"><div class="info-item"><span class="info-label">Address</span><span class="info-value">{{ $student->address }}</span></div></div>
                    @endif
                </div>
            </div>
        </article>

        <article class="dashboard-card">
            <div class="card-header">
                <div><h2 class="card-title"><i class="fa-solid fa-people-roof me-2"></i>Family &amp; siblings</h2><small class="text-muted">Family links never alter admission or fee history.</small></div>
                @if($student->family && in_array(Auth::user()->role ?: 'admin',['admin','accountant']))<a class="btn btn-outline-primary btn-sm" href="{{ route('families.show',$student->family) }}">Open family</a>@endif
            </div>
            <div class="card-body">
                @if($student->family)
                    <div class="student-family-heading"><div><span class="family-code">{{ $student->family->family_code }}</span><strong>{{ $student->family->display_name }}</strong><small>{{ $student->family->primaryGuardian?->name }} · {{ $student->family->primaryGuardian?->phone }}</small></div></div>
                    <div class="sibling-strip mt-3">
                        @foreach($student->family->students as $sibling)
                        <a href="{{ route('students.show',$sibling) }}" class="sibling-mini-card {{ $sibling->id===$student->id?'is-current':'' }}"><span>{{ strtoupper(substr($sibling->name,0,1)) }}</span><div><strong>{{ $sibling->name }}</strong><small>{{ $sibling->schoolClass?->name ?: $sibling->class }} · {{ ucfirst($sibling->status) }}</small></div></a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-3"><span class="empty-state-icon"><i class="fa-solid fa-link"></i></span><h5>No family linked</h5><p>An administrator can link this student from Families &amp; Guardians.</p>@if((Auth::user()->role ?: 'admin')==='admin')<a class="btn btn-primary btn-sm" href="{{ route('families.index',['search'=>$student->name]) }}">Find or create family</a>@endif</div>
                @endif
            </div>
        </article>

        <article class="dashboard-card">
            <div class="card-header"><h2 class="card-title"><i class="fa-solid fa-timeline me-2"></i>Enrollment history</h2></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Session</th><th>Class</th><th>Section</th><th>Status</th></tr></thead><tbody>@forelse($student->enrollments as $enrollment)<tr><td>{{ $enrollment->academicSession?->name }}</td><td>{{ $enrollment->schoolClass?->name }}</td><td>{{ $enrollment->section?->name ?: '—' }}</td><td><span class="status-pill status-paid">{{ ucfirst($enrollment->status) }}</span></td></tr>@empty<tr><td colspan="4" class="text-muted">No enrollment history yet.</td></tr>@endforelse</tbody></table></div>
        </article>

        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Fee history</h2>
                    <small class="text-muted">{{ $student->payments->count() }} {{ Str::plural('receipt', $student->payments->count()) }}</small>
                </div>
                <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add fee</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Date</th><th>Receipt</th><th class="d-none d-md-table-cell">Note</th><th class="text-end">Amount</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        @forelse($student->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                <td><span class="badge bg-primary">{{ $payment->receipt_number }}</span></td>
                                <td class="d-none d-md-table-cell text-muted">{{ Str::limit($payment->description ?: '—', 40) }}</td>
                                <td class="text-end"><strong class="amount-highlight">Rs {{ number_format($payment->amount, 0) }}</strong></td>
                                <td class="text-end"><a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-receipt"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><span class="empty-state-icon"><i class="fa-solid fa-receipt"></i></span><h5>No fee history</h5><p>No payments have been recorded for this student.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <aside>
        <article class="dashboard-card">
            <div class="card-header"><h2 class="card-title"><i class="fa-solid fa-chart-simple me-2"></i>Fee summary</h2></div>
            <div class="card-body">
                <div class="stat-value mb-1">Rs {{ number_format($remainingThisMonth, 0) }}</div>
                <p class="text-muted mb-4">Remaining for {{ now()->format('F') }}</p>
                <div class="info-item mb-3"><span class="info-label">Monthly fee</span><span class="info-value">Rs {{ number_format($student->monthly_fee, 0) }}</span></div>
                <div class="info-item mb-3"><span class="info-label">Paid this month</span><span class="info-value">Rs {{ number_format($paidThisMonth, 0) }}</span></div>
                <div class="info-item mb-3"><span class="info-label">Latest payment</span><span class="info-value">{{ optional($student->payments->first())->payment_date?->format('d M Y') ?: 'No payment yet' }}</span></div>
                <div class="info-item mb-3"><span class="info-label">All-time collected</span><span class="info-value">Rs {{ number_format($student->payments->sum('amount'), 0) }}</span></div>
                <div class="info-item"><span class="info-label">Receipts issued</span><span class="info-value">{{ $student->payments->count() }}</span></div>
            </div>
        </article>

        <article class="dashboard-card">
            <div class="card-header"><h2 class="card-title"><i class="fa-solid fa-bolt me-2"></i>Actions</h2></div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-primary"><i class="fa-solid fa-wallet me-2"></i>Record fee</a>
                <a href="{{ route('students.edit', $student) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen me-2"></i>Edit record</a>
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>All students</a>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fa-solid fa-trash me-2"></i>Delete student</button>
            </div>
        </article>
    </aside>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Delete student?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">This permanently removes <strong>{{ $student->name }}</strong> and all linked fee records.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('students.destroy', $student) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-danger">Delete permanently</button></form>
            </div>
        </div>
    </div>
</div>
@endsection
