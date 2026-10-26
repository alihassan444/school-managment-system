<div class="fee-structure-document">
    <header class="fee-structure-header">
        <img src="{{ $logoSource }}" alt="{{ $school['name'] }} logo">
        <div><h1>{{ $school['name'] }}</h1><h2>Family Fee Slip</h2><p>{{ $school['address'] }}{{ $school['phone'] ? ' · '.$school['phone'] : '' }}</p></div>
    </header>
    <section class="fee-structure-meta">
        <div><span>Family ID</span><strong>{{ $family->family_code }}</strong></div>
        <div><span>Family</span><strong>{{ $family->display_name }}</strong></div>
        <div><span>Parent / Guardian</span><strong>{{ $family->primaryGuardian?->name ?: 'Not assigned' }}</strong></div>
        <div><span>Fee Month</span><strong>{{ $month->format('F Y') }}</strong></div>
    </section>
    <main>
        <h3>Students</h3>
        <table><thead><tr><th>#</th><th>Student Name</th><th>Class</th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ $loop->iteration }}</td><td><strong>{{ $row['student']->name }}</strong></td><td>{{ $row['student']->schoolClass?->name ?: $row['student']->class }}</td></tr>@endforeach
        </tbody></table>
    </main>
    <section class="fee-structure-total">
        <span>Current Month Fee</span><strong>Rs {{ number_format($totals['fee'], 2) }}</strong>
        <span>Previous Outstanding</span><strong>Rs {{ number_format($totals['previous'], 2) }}</strong>
        <span>Total Payable</span><strong>Rs {{ number_format($totals['payable'], 2) }}</strong>
        <span>Amount Received</span><strong>Rs {{ number_format($totals['paid'], 2) }}</strong>
        <span>Remaining Balance</span><strong>Rs {{ number_format($totals['outstanding'], 2) }}</strong>
    </section>
    <footer><span>Generated {{ now()->format('d M Y, h:i A') }}</span><span class="signature">Authorized Signature</span></footer>
</div>
