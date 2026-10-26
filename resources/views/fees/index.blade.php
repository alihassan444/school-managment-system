@extends('layouts.dashboard')
@section('title', 'Fees')
@section('eyebrow', 'Invoices & collections')
@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3">
    <div><span class="eyebrow">Fee operations</span><h1>Monthly invoices</h1><p>Generate dues, follow partial payments and open printable fee vouchers.</p></div>
    <a class="btn btn-outline-primary" href="{{ route('payments.index') }}"><i class="fa-solid fa-receipt me-2"></i>Payment history</a>
</div>
@include('partials.flash')
@include('fees._navigation')
@include('partials.month-navigator', [
    'routeName' => 'fees.index',
    'month' => $month,
    'monthInputId' => 'individualBillingMonth',
    'routeParams' => ['status' => $status, 'search' => $search],
])
<div class="stats-grid mb-4">
    @foreach([['Invoiced',$summary['invoiced'],'primary','fa-file-invoice-dollar'],['Collected',$summary['paid'],'success','fa-circle-check'],['Outstanding',$summary['outstanding'],'danger','fa-hourglass-half'],['Overdue invoices',$summary['overdue'],'warning','fa-triangle-exclamation']] as [$label,$value,$tone,$icon])
    <article class="stat-card tone-{{ $tone }}"><span class="stat-icon"><i class="fa-solid {{ $icon }}"></i></span><div class="stat-value">{{ $label === 'Overdue invoices' ? number_format($value) : 'Rs '.number_format($value) }}</div><div class="stat-label">{{ $label }}</div></article>
    @endforeach
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-7"><div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-filter"></i>Invoice register</h2></div><div class="card-body"><form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">Month</label><input class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}"></div>
        <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['unpaid','partial','paid','overdue'] as $option)<option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Student or family</label><input class="form-control" name="search" value="{{ $search }}" placeholder="Name, ID, roll or family"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
    </form></div></div></div>
    <div class="col-xl-5"><div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-wand-magic-sparkles"></i>Generate monthly invoices</h2></div><div class="card-body"><form method="POST" action="{{ route('fees.generate') }}" class="row g-3">@csrf
        <div class="col-6"><label class="form-label">Billing month</label><input class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}" required></div>
        <div class="col-6"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date" value="{{ $month->copy()->day($dueDay)->toDateString() }}" required></div>
        <div class="col-12"><button class="btn btn-primary w-100"><i class="fa-solid fa-gears me-2"></i>Generate missing invoices</button></div>
    </form></div></div></div>
</div>
@if((Auth::user()->role ?: 'admin') === 'admin')
<div class="surface-card mb-4"><div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-file-circle-plus"></i>Create one missing invoice</h2><small class="text-muted">Uses the student's effective monthly fee and opens an existing invoice instead of duplicating it.</small></div></div><div class="card-body"><form method="POST" action="{{ route('fees.invoices.store') }}" class="row g-3 align-items-end">@csrf
    <div class="col-lg-4"><label class="form-label">Student</label><select class="form-select" name="student_id" required><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->admission_no ?: $student->roll_no ?: 'Student #'.$student->id }}</option>@endforeach</select></div>
    <div class="col-lg-2"><label class="form-label">Billing month</label><input class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}" required></div>
    <div class="col-lg-2"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date" value="{{ $month->copy()->day(10)->toDateString() }}" required></div>
    <div class="col-lg-2"><label class="form-label">Notes</label><input class="form-control" name="notes"></div>
    <div class="col-lg-2"><button class="btn btn-outline-primary w-100">Create / Open</button></div>
</form></div></div>
@endif
<div class="surface-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Invoice</th><th>Student</th><th>Class</th><th>Due date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr>
    <td><strong>{{ $invoice->invoice_number }}</strong>@if($invoice->student->family)<small class="d-block text-muted">{{ $invoice->student->family->family_code }}</small>@endif</td><td>{{ $invoice->student->name }}<small class="d-block text-muted">{{ $invoice->student->admission_no ?: $invoice->student->roll_no }}</small></td><td>{{ $invoice->student->schoolClass?->name ?: $invoice->student->class }}</td><td>{{ $invoice->due_date->format('d M Y') }}</td><td>Rs {{ number_format($invoice->total) }}</td><td class="text-success">Rs {{ number_format($invoice->paid) }}</td><td class="text-danger">Rs {{ number_format(max($invoice->total-$invoice->paid,0)) }}</td><td><span class="status-pill status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td><td class="text-end"><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('fees.show',$invoice) }}">Open</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('fees.students.ledger',$invoice->student) }}">Ledger</a>@if((Auth::user()->role ?: 'admin') === 'admin')<a class="btn btn-sm btn-outline-secondary" href="{{ route('fees.show',$invoice) }}#add-charge">Add charge</a>@endif</div></td>
</tr>@empty<tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon"><i class="fa-solid fa-file-circle-plus"></i></div><h5>No invoices for {{ $month->format('F Y') }}</h5><p>Generate monthly invoices using the panel above.</p></div></td></tr>@endforelse
</tbody></table></div>@if($invoices->hasPages())<div class="card-body border-top">{{ $invoices->links() }}</div>@endif</div>
@endsection
