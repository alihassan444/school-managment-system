@extends('layouts.dashboard')
@section('title','Combined family receipt')
@section('eyebrow','Combined payment')
@section('content')
<div class="page-header no-print d-flex justify-content-between align-items-start gap-3">
    <div><span class="eyebrow">{{ $familyPayment->receipt_number }}</span><h1>Combined family receipt</h1><p>One family-level receipt with the underlying student allocations kept private.</p></div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('family-fees.receipts.pdf',$familyPayment) }}"><i class="fa-solid fa-file-pdf me-2"></i>Download PDF</a><button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print receipt</button>@if((Auth::user()->role ?: 'admin')==='admin' && $familyPayment->status==='completed')<form method="POST" action="{{ route('family-fees.receipts.reverse',$familyPayment) }}" onsubmit="return confirm('Reverse this complete family payment? Student invoice balances will be restored.')">@csrf<button class="btn btn-outline-danger">Reverse</button></form>@endif</div>
</div>
@include('partials.flash')
<article class="family-document invoice-sheet">
    <header class="family-document-header">
        <img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="{{ $school['name'] }} crest">
        <div><h2>{{ $school['name'] }}</h2><p>Combined Family Fee Receipt</p><small>{{ $school['address'] }} @if($school['phone']) · {{ $school['phone'] }} @endif</small></div>
        <div class="family-document-reference"><span>Receipt</span><strong>{{ $familyPayment->receipt_number }}</strong></div>
    </header>
    <section class="family-document-meta">
        <div><span>Family ID</span><strong>{{ $familyPayment->family->family_code }}</strong></div>
        <div><span>Parent / Guardian</span><strong>{{ $familyPayment->family->primaryGuardian?->name ?: 'Not assigned' }}</strong></div>
        <div><span>Fee Month</span><strong>{{ $feeMonth->format('F Y') }}</strong></div>
        <div><span>Payment Date</span><strong>{{ $familyPayment->payment_date->format('d M Y') }}</strong></div>
    </section>
    <section class="family-document-students">
        <h3>Students</h3>
        <table><thead><tr><th>#</th><th>Student Name</th><th>Class</th></tr></thead><tbody>
        @foreach($students as $student)<tr><td>{{ $loop->iteration }}</td><td><strong>{{ $student->name }}</strong></td><td>{{ $student->schoolClass?->name ?: $student->class }}</td></tr>@endforeach
        </tbody></table>
    </section>
    <section class="family-document-totals">
        <div class="payable"><span>Total Family Fee / Payable</span><strong>{{ $school['currency'] }} {{ number_format($summary['total_payable']) }}</strong></div>
        <div><span>Amount Paid</span><strong>{{ $school['currency'] }} {{ number_format($summary['amount_paid']) }}</strong></div>
        <div><span>Payment Method</span><strong>{{ ucfirst($familyPayment->payment_method) }}</strong></div>
        @if($familyPayment->reference_number)<div><span>Reference</span><strong>{{ $familyPayment->reference_number }}</strong></div>@endif
        <div class="remaining"><span>Remaining Balance</span><strong>{{ $school['currency'] }} {{ number_format($summary['remaining_balance']) }}</strong></div>
    </section>
    @if($familyPayment->status==='reversed')<div class="family-document-alert">This receipt has been reversed and is no longer a completed payment.</div>@endif
    <footer class="family-document-footer"><strong>Thank you for your payment.</strong><span>Computer-generated receipt. Signature or stamp is not required.</span></footer>
</article>
@endsection
