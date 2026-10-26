@extends('layouts.dashboard')

@section('title', 'Fees')
@section('eyebrow', 'Fee management')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-end gap-3">
        <div>
            <span class="page-kicker">Fee ledger</span>
            <h1 class="page-title">Every payment, accounted for.</h1>
            <p class="page-subtitle">A clean ledger of fee collections and printable receipts.</p>
        </div>
        <a href="{{ route('payments.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Record fee
        </a>
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

@include('fees._navigation')

<div class="mini-metrics">
    <div class="mini-metric">
        <strong>{{ number_format($payments->total()) }}</strong>
        <span>Matching receipts</span>
    </div>
    <div class="mini-metric">
        <strong>Rs {{ number_format($totalCollected, 0) }}</strong>
        <span>Matching total</span>
    </div>
    <div class="mini-metric">
        <strong>{{ request('date_from') ?: 'Any' }}</strong>
        <span>From date</span>
    </div>
    <div class="mini-metric">
        <strong>{{ request('date_to') ?: 'Today' }}</strong>
        <span>To date</span>
    </div>
</div>

<div class="dashboard-card filter-panel">
    <div class="card-body">
        <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="search" class="form-label">Search ledger</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Student name or receipt number">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_from" class="form-label">From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_to" class="form-label">To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Apply filters</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><i class="fa-solid fa-receipt me-2"></i>Fee receipts</h2>
            <small class="text-muted">Showing {{ $payments->count() }} of {{ $payments->total() }}</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th class="d-none d-md-table-cell">Receipt</th>
                    <th class="d-none d-lg-table-cell">Note</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>
                            <strong class="d-block">{{ $payment->payment_date->format('d M Y') }}</strong>
                            <small class="text-muted">{{ $payment->payment_date->format('D') }}</small>
                        </td>
                        <td>
                            <div class="student-identity">
                                <span class="student-avatar">{{ strtoupper(substr($payment->student->name, 0, 1)) }}</span>
                                <span>
                                    <strong>{{ $payment->student->name }}</strong>
                                    <small>Class {{ $payment->student->class }}</small>
                                </span>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell"><span class="badge bg-primary">{{ $payment->receipt_number }}</span></td>
                        <td class="d-none d-lg-table-cell text-muted">{{ Str::limit($payment->description ?: '—', 34) }}</td>
                        <td class="text-end"><strong class="amount-highlight">Rs {{ number_format($payment->amount, 0) }}</strong></td>
                        <td>
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-primary btn-sm" title="Open receipt"><i class="fa-solid fa-receipt"></i></a>
                                <a href="{{ route('students.show', $payment->student) }}" class="btn btn-outline-secondary btn-sm" title="Open student"><i class="fa-solid fa-user"></i></a>
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Delete receipt {{ $payment->receipt_number }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete payment"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-state-icon"><i class="fa-solid fa-receipt"></i></span>
                                <h5>No fee receipts found</h5>
                                <p>Try clearing the filters or record the first payment.</p>
                                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">Record fee</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="card-footer">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
