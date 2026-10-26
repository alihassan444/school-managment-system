@extends('layouts.dashboard')
@section('title','Combined family invoice')
@section('eyebrow','Combined billing statement')
@section('content')
<div class="page-header no-print d-flex justify-content-between align-items-start gap-3">
    <div><span class="eyebrow">{{ $statement->statement_number }}</span><h1>Combined family fee invoice</h1><p>A compact family summary backed by the original student ledgers.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('family-fees.statements.pdf',$statement) }}"><i class="fa-solid fa-file-pdf me-2"></i>Download PDF</a><button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print invoice</button></div>
</div>
@include('partials.flash')
<article class="family-document invoice-sheet">
    <header class="family-document-header">
        <img src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="{{ $school['name'] }} crest">
        <div><h2>{{ $school['name'] }}</h2><p>Combined Family Fee Invoice</p><small>{{ $school['address'] }} @if($school['phone']) · {{ $school['phone'] }} @endif</small></div>
        <div class="family-document-reference"><span>Invoice</span><strong>{{ $statement->statement_number }}</strong></div>
    </header>
    <section class="family-document-meta">
        <div><span>Family ID</span><strong>{{ $statement->family->family_code }}</strong></div>
        <div><span>Parent / Guardian</span><strong>{{ $statement->family->primaryGuardian?->name ?: 'Not assigned' }}</strong></div>
        <div><span>Fee Month</span><strong>{{ $statement->billing_month->format('F Y') }}</strong></div>
        <div><span>Due Date</span><strong>{{ $statement->due_date->format('d M Y') }}</strong></div>
    </section>
    <section class="family-document-students">
        <h3>Students</h3>
        <table><thead><tr><th>#</th><th>Student Name</th><th>Class</th></tr></thead><tbody>
        @foreach($students as $student)<tr><td>{{ $loop->iteration }}</td><td><strong>{{ $student->name }}</strong></td><td>{{ $student->schoolClass?->name ?: $student->class }}</td></tr>@endforeach
        </tbody></table>
    </section>
    <section class="family-document-totals">
        @if($summary['previous_balance'] > 0)<div><span>Previous Balance</span><strong>{{ $school['currency'] }} {{ number_format($summary['previous_balance']) }}</strong></div>@endif
        <div><span>Current Month Family Fee</span><strong>{{ $school['currency'] }} {{ number_format($summary['current_month_fee']) }}</strong></div>
        <div class="payable"><span>Total Payable</span><strong>{{ $school['currency'] }} {{ number_format($summary['total_payable']) }}</strong></div>
        <div><span>Amount Paid</span><strong>{{ $school['currency'] }} {{ number_format($summary['amount_paid']) }}</strong></div>
        <div class="remaining"><span>Remaining Balance</span><strong>{{ $school['currency'] }} {{ number_format($summary['remaining_balance']) }}</strong></div>
    </section>
    <footer class="family-document-footer"><strong>Thank you.</strong><span>Computer-generated family invoice. Individual student ledgers remain available in the administration portal.</span></footer>
</article>
@endsection
