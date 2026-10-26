@extends('layouts.dashboard')
@section('title', 'Family Fees')
@section('eyebrow', 'Combined collection')
@section('content')
<div class="page-header d-flex justify-content-between align-items-start gap-3">
    <div><span class="eyebrow">Sibling fee collection</span><h1>Family / combined fees</h1><p>Review one billing month at a time while preserving every child’s independent ledger.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('fees.index', ['month' => $selectedMonth->format('Y-m')]) }}">Individual fees</a>@if((Auth::user()->role ?: 'admin') === 'admin')<a class="btn btn-outline-secondary" href="{{ route('sibling-discounts.index') }}">Discount rules</a>@endif</div>
</div>
@include('partials.flash')

@include('fees._navigation')

@include('partials.month-navigator', [
    'routeName' => 'family-fees.index',
    'month' => $selectedMonth,
    'monthInputId' => 'familyBillingMonth',
    'routeParams' => ['family_id' => $family?->id, 'search' => $search],
])

<div class="surface-card mb-4"><div class="card-body"><form method="GET" class="row g-3 align-items-end">
    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
    <div class="col-lg-5"><label class="form-label">Search family, guardian, phone, or student</label><input class="form-control" name="search" value="{{ $search }}" placeholder="FAM-0001 or parent/student name"></div>
    <div class="col-lg-5"><label class="form-label">Select family</label><select class="form-select" name="family_id"><option value="">Choose family</option>@foreach($families as $item)<option value="{{ $item->id }}" @selected($family?->id === $item->id)>{{ $item->family_code }} · {{ $item->display_name }} · {{ $item->primaryGuardian?->name }}</option>@endforeach</select></div>
    <div class="col-lg-2"><button class="btn btn-primary w-100">Open family</button></div>
</form></div></div>

@if($families->isNotEmpty())
<div class="family-fee-directory mb-4">
@foreach($families as $directoryFamily)
@php
    $directoryInvoices = $directoryFamily->students->flatMap->feeInvoices;
    $directoryTotalCents = $directoryInvoices->sum(fn ($invoice) => \App\Models\FeeInvoice::toCents($invoice->total));
    $directoryPaidCents = $directoryInvoices->sum(fn ($invoice) => \App\Models\FeeInvoice::toCents($invoice->paid));
    $directoryTotal = \App\Models\FeeInvoice::fromCents($directoryTotalCents);
    $directoryPaid = \App\Models\FeeInvoice::fromCents($directoryPaidCents);
    $directoryDue = \App\Models\FeeInvoice::fromCents(max($directoryTotalCents - $directoryPaidCents, 0));
@endphp
<details class="surface-card family-fee-card" @if($family?->id === $directoryFamily->id) open @endif>
    <summary><span><span class="family-code">{{ $directoryFamily->family_code }}</span><strong>{{ $directoryFamily->display_name }}</strong><small>{{ $directoryFamily->primaryGuardian?->name }} · {{ $directoryFamily->students->count() }} children</small></span><span><small>{{ $selectedMonth->format('M Y') }} total</small><strong>Rs {{ number_format($directoryTotal,2) }}</strong><small>Paid Rs {{ number_format($directoryPaid,2) }} · Due Rs {{ number_format($directoryDue,2) }}</small></span></summary>
    <div class="family-fee-card-body">
        @foreach($directoryFamily->students as $child)
        @php($childInvoice = $child->feeInvoices->first())
        <div><span><strong>{{ $child->name }}</strong><small>{{ $child->admission_no ?: $child->roll_no ?: 'Student #'.$child->id }} · {{ $child->schoolClass?->name ?: $child->class }}</small><small>Monthly fee Rs {{ number_format($childInvoice?->total ?? $child->monthly_fee,2) }} · Outstanding Rs {{ number_format($childInvoice ? \App\Models\FeeInvoice::fromCents(max(\App\Models\FeeInvoice::toCents($childInvoice->total)-\App\Models\FeeInvoice::toCents($childInvoice->paid),0)) : 0,2) }}</small></span><span class="family-card-actions"><a href="{{ route('fees.students.ledger',$child) }}">Student ledger</a>@if($childInvoice)<a href="{{ route('fees.show',$childInvoice) }}">View invoice</a>@if((Auth::user()->role ?: 'admin') === 'admin')<a href="{{ route('fees.show',$childInvoice) }}#add-charge">Add charge</a>@endif @else<small>No invoice</small>@endif</span></div>
        @endforeach
        <a class="btn btn-outline-primary btn-sm" href="{{ route('family-fees.index',['family_id'=>$directoryFamily->id,'month'=>$selectedMonth->format('Y-m'),'search'=>$search]) }}">Open family fees</a>
    </div>
</details>
@endforeach
</div>
@endif

@if($family)
<div class="family-fee-hero mb-4"><div><span class="family-code">{{ $family->family_code }}</span><h2>{{ $family->display_name }}</h2><p>{{ $family->primaryGuardian?->name }} · {{ $family->primaryGuardian?->phone }} · {{ $family->students->count() }} children · {{ $selectedMonth->format('F Y') }}</p></div><div><small>Combined outstanding through {{ $selectedMonth->format('M Y') }}</small><strong>Rs {{ number_format($totalOutstanding) }}</strong></div></div>

<div class="d-flex flex-wrap justify-content-end gap-2 mb-3"><a class="btn btn-outline-secondary" href="{{ route('families.show',$family) }}"><i class="fa-solid fa-link me-2"></i>View / add family members</a><a class="btn btn-outline-secondary" href="{{ route('fees.families.ledger',$family) }}"><i class="fa-solid fa-book-open me-2"></i>Family ledger</a><a class="btn btn-outline-primary" target="_blank" href="{{ route('family-fees.structure',[$family,'month'=>$selectedMonth->format('Y-m')]) }}"><i class="fa-solid fa-print me-2"></i>Print Family Fee Slip</a></div>

<div class="stats-grid family-period-stats mb-4">
    <article class="stat-card tone-primary"><div class="stat-value">Rs {{ number_format($currentFees) }}</div><div class="stat-label">Current month fees</div></article>
    <article class="stat-card tone-success"><div class="stat-value">Rs {{ number_format($currentPaid) }}</div><div class="stat-label">Applied to {{ $selectedMonth->format('M Y') }}</div></article>
    <article class="stat-card tone-warning"><div class="stat-value">Rs {{ number_format($currentOutstanding) }}</div><div class="stat-label">Current month outstanding</div></article>
    <article class="stat-card tone-danger"><div class="stat-value">Rs {{ number_format($previousOutstanding) }}</div><div class="stat-label">Previous months outstanding</div></article>
    <article class="stat-card tone-info"><div class="stat-value">Rs {{ number_format($totalOutstanding) }}</div><div class="stat-label">Total through selected month</div></article>
</div>

<form id="statementForm" method="POST" action="{{ route('family-fees.statements.store') }}">@csrf<input type="hidden" name="family_id" value="{{ $family->id }}"><input type="hidden" name="billing_month" value="{{ $selectedMonth->format('Y-m') }}"></form>
<form id="paymentForm" method="POST" action="{{ route('family-fees.payments.store') }}">@csrf<input type="hidden" name="family_id" value="{{ $family->id }}"><input type="hidden" name="billing_month" value="{{ $selectedMonth->format('Y-m') }}"><input type="hidden" name="payment_scope" value="all_outstanding"><input type="hidden" name="idempotency_key" value="{{ (string) Str::uuid() }}"></form>

<div class="surface-card mb-4"><div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-layer-group"></i>{{ $selectedMonth->format('F Y') }} invoices</h2><small class="text-muted">Only invoices belonging to the selected billing month are shown.</small></div></div><div class="table-responsive"><table class="table align-middle mb-0 family-allocation-table"><thead><tr><th>Student</th><th>Invoice / due</th><th>Total</th><th>Already paid</th><th>Outstanding</th></tr></thead><tbody>
@forelse($monthInvoices as $invoice)@php($balance = \App\Models\FeeInvoice::fromCents(max(\App\Models\FeeInvoice::toCents($invoice->total)-\App\Models\FeeInvoice::toCents($invoice->paid),0)))<tr><td><strong>{{ $invoice->student->name }}</strong><small class="d-block text-muted">{{ $invoice->student->schoolClass?->name ?: $invoice->student->class }}</small></td><td><a href="{{ route('fees.show', $invoice) }}">{{ $invoice->invoice_number }}</a><small class="d-block text-muted">Due {{ $invoice->due_date->format('d M Y') }}</small>@if((Auth::user()->role ?: 'admin') === 'admin')<a class="d-block small" href="{{ route('fees.show',$invoice) }}#add-charge">Add charge</a>@endif</td><td>Rs {{ number_format($invoice->total,2) }}@if($invoice->sibling_discount > 0)<small class="d-block text-success">Sibling discount Rs {{ number_format($invoice->sibling_discount,2) }}</small>@endif</td><td>Rs {{ number_format($invoice->paid,2) }}</td><td><strong class="{{ \App\Models\FeeInvoice::toCents($balance) > 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($balance,2) }}</strong><small class="d-block text-muted">{{ ucfirst($invoice->status) }}</small></td></tr>
@empty<tr><td colspan="5"><div class="empty-state"><span class="empty-state-icon"><i class="fa-solid fa-file-circle-plus"></i></span><h5>No invoices for {{ $selectedMonth->format('F Y') }}</h5><p>Switch months to inspect history. Viewing a month never generates charges.</p><a class="btn btn-outline-primary btn-sm" href="{{ route('fees.index', ['month' => $selectedMonth->format('Y-m')]) }}">Open monthly invoice register</a></div></td></tr>@endforelse
</tbody></table></div></div>

@if($totalOutstanding > 0)<div class="row g-4 mb-4"><div class="col-xl-5"><div class="surface-card h-100"><div class="card-header"><h2 class="card-title"><i class="fa-solid fa-file-lines"></i>Combined family invoice</h2></div><div class="card-body"><p class="text-muted">Creates the existing simplified A4 invoice through {{ $selectedMonth->format('F Y') }}. Previous dues remain linked to their original invoices and are not charged twice.</p><button class="btn btn-outline-primary w-100" type="submit" form="statementForm"><i class="fa-solid fa-print me-2"></i>Generate family invoice</button></div></div></div>
<div class="col-xl-7"><div class="surface-card h-100"><div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-money-bill-transfer"></i>Record family payment</h2><small class="text-muted">The system reconciles the oldest eligible dues automatically.</small></div></div><div class="card-body"><div class="row g-3">
    <div class="col-12"><div class="alert alert-light border"><strong>Total payable through {{ $selectedMonth->format('F Y') }}: Rs {{ number_format($totalOutstanding,2) }}</strong><div class="small text-muted">Payments are applied automatically to the oldest outstanding invoices first.</div></div></div>
    <div class="col-md-6"><label class="form-label">Amount received *</label><div class="input-group"><span class="input-group-text">Rs</span><input class="form-control" id="familyPaymentAmount" type="number" name="amount" min="0.01" max="{{ $totalOutstanding }}" step="0.01" required form="paymentForm"></div></div>
    <div class="col-md-4"><label class="form-label">Payment method</label><select class="form-select" name="payment_method" form="paymentForm">@foreach(['cash','bank','card','cheque','other'] as $method)<option value="{{ $method }}">{{ ucfirst($method) }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Payment date</label><input class="form-control" type="date" name="payment_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required form="paymentForm"></div>
    <div class="col-md-4"><label class="form-label">Reference (optional)</label><input class="form-control" name="reference_number" form="paymentForm"></div>
    <div class="col-12"><label class="form-label">Notes (optional)</label><input class="form-control" name="notes" form="paymentForm"></div>
</div><div class="family-payment-preview mt-3" data-total="{{ $totalOutstanding }}"><div><span>Total Payable</span><strong id="familyOutstandingBalance">Rs 0</strong></div><div><span>Remaining After Payment</span><strong id="familyRemainingBalance">Rs 0</strong></div></div><button class="btn btn-primary w-100 mt-3" type="submit" form="paymentForm"><i class="fa-solid fa-lock me-2"></i>Confirm &amp; record family payment</button></div></div></div></div>@endif

<div class="row g-4"><div class="col-xl-7"><div class="surface-card h-100"><div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-file-invoice-dollar"></i>Billing-period payment history</h2><small class="text-muted">Amounts applied to {{ $selectedMonth->format('F Y') }} invoices, regardless of when cash was received.</small></div></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Receipt</th><th>Payment date</th><th>Applied to month</th><th>Method</th><th></th></tr></thead><tbody>@forelse($billingPayments as $payment)<tr><td><strong>{{ $payment->receipt_number }}</strong></td><td>{{ $payment->payment_date->format('d M Y') }}</td><td>Rs {{ number_format($payment->selected_month_applied) }}</td><td>{{ ucfirst($payment->payment_method) }}</td><td class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('family-fees.receipts.show', $payment) }}">Receipt</a></td></tr>@empty<tr><td colspan="5" class="text-muted">No family payments have been applied to this billing month.</td></tr>@endforelse</tbody></table></div></div></div>
<div class="col-xl-5"><div class="surface-card h-100"><div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-cash-register"></i>Cash received in month</h2><small class="text-muted">Transactions physically received during {{ $selectedMonth->format('F Y') }}.</small></div></div><div class="card-body"><div class="report-summary-list"><div><span>Cash collection</span><strong>Rs {{ number_format($cashPayments->sum('total_amount')) }}</strong></div><div><span>Transactions</span><strong>{{ $cashPayments->count() }}</strong></div></div>@forelse($cashPayments->take(5) as $payment)<a class="activity-row text-decoration-none" href="{{ route('family-fees.receipts.show', $payment) }}"><span class="activity-icon"><i class="fa-solid fa-receipt"></i></span><span class="activity-main"><strong>{{ $payment->receipt_number }}</strong><small>{{ $payment->payment_date->format('d M Y') }}</small></span><span class="activity-amount">Rs {{ number_format($payment->total_amount) }}</span></a>@empty<p class="text-muted mb-0">No family cash receipts in this month.</p>@endforelse</div></div></div></div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const amount = document.getElementById('familyPaymentAmount');
    const preview = document.querySelector('.family-payment-preview');
    const outstandingOutput = document.getElementById('familyOutstandingBalance');
    const remainingOutput = document.getElementById('familyRemainingBalance');
    const money = new Intl.NumberFormat('en-PK', { maximumFractionDigits: 2 });
    const update = () => {
        if (!preview) return;
        const outstanding = Number(preview.dataset.total) || 0;
        const received = Math.max(Number(amount?.value) || 0, 0);
        if (amount) amount.max = outstanding.toFixed(2);
        if (outstandingOutput) outstandingOutput.textContent = `Rs ${money.format(outstanding)}`;
        if (remainingOutput) remainingOutput.textContent = `Rs ${money.format(Math.max(outstanding - received, 0))}`;
    };
    amount?.addEventListener('input', update);
    update();
});
</script>
@endpush
