<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }} · Al Suffaa School</title>
    <link rel="icon" href="{{ asset('images/al-suffaa-school-crest.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; padding: 28px 14px; color: #101820; font-family: 'DM Sans', sans-serif; background: #edf2ef; }
        .receipt-toolbar { width: min(920px, 100%); display: flex; justify-content: space-between; gap: 10px; margin: 0 auto 14px; }
        .receipt-paper { width: min(920px, 100%); margin: auto; padding: 24px; border: 1px solid #d5ded9; background: #fff; box-shadow: 0 24px 70px rgba(11,31,51,.14); }
        .school-header { display: grid; grid-template-columns: 88px minmax(0,1fr) auto; align-items: center; gap: 17px; }
        .school-logo { width: 82px; height: 82px; display: block; object-fit: contain; }
        .school-name { margin: 0; color: #081a29; font: 800 clamp(1.7rem,4vw,2.75rem) 'Manrope',sans-serif; letter-spacing: .035em; text-transform: uppercase; }
        .campus-lines { margin-top: 5px; font-size: .83rem; font-weight: 700; line-height: 1.55; }
        .receipt-ref { min-width: 155px; padding: 10px 12px; border: 2px solid #101820; text-align: center; }
        .receipt-ref span,.receipt-ref strong { display: block; }
        .receipt-ref span { font-size: .65rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .receipt-ref strong { margin-top: 4px; font: 800 1rem 'Manrope',sans-serif; }
        .receipt-heading { width: fit-content; margin: 18px auto; padding: 7px 28px; border: 3px solid #101820; border-radius: 7px; font: 800 1.15rem 'Manrope',sans-serif; }
        .student-box { position: relative; display: grid; grid-template-columns: minmax(0,1fr) 245px; border: 3px solid #101820; }
        .student-details { display: grid; gap: 7px; padding: 20px; }
        .student-line { display: grid; grid-template-columns: 160px minmax(0,1fr); gap: 12px; }
        .student-line span { font-weight: 800; }
        .student-line strong { font-weight: 600; }
        .date-box { align-self: start; display: grid; grid-template-columns: 78px 1fr; padding: 10px 14px; border-left: 3px solid #101820; border-bottom: 3px solid #101820; font-size: 1rem; }
        .date-box span { font-weight: 800; }
        .fee-tables { display: grid; grid-template-columns: 1.15fr .85fr; gap: 18px; margin-top: 10px; }
        .receipt-table { width: 100%; border: 3px solid #101820; border-collapse: collapse; }
        .receipt-table td { padding: 9px 14px; font-size: .95rem; }
        .receipt-table td:first-child { font-weight: 800; }
        .receipt-table td:last-child { width: 145px; border-left: 2px solid #101820; text-align: right; font-variant-numeric: tabular-nums; }
        .summary-table tr:nth-last-child(-n+3) { border-top: 1px solid #cbd4cf; }
        .summary-table tr:last-child { background: #eef8f4; }
        .summary-table tr:last-child td { font-size: 1.05rem; }
        .receipt-message { display: grid; place-content: center; min-height: 96px; margin-top: 10px; padding: 14px; border: 3px solid #101820; color: #4d5c58; text-align: center; font-size: 1rem; line-height: 1.5; }
        .receipt-status { display: inline-flex; align-items: center; gap: 7px; margin-top: 12px; padding: 7px 11px; border-radius: 999px; color: #08705f; background: #dff5ee; font-size: .72rem; font-weight: 800; }
        .receipt-note { margin: 14px 0 0; padding: 11px 13px; border-left: 4px solid #0e8f79; color: #52635e; background: #f5f8f6; font-size: .8rem; }
        .receipt-footer { display: flex; justify-content: space-between; gap: 20px; margin-top: 16px; color: #63736e; font-size: .7rem; }
        @media(max-width:700px){
            body{padding:12px 8px}.receipt-paper{padding:15px}.school-header{grid-template-columns:62px minmax(0,1fr)}.school-logo{width:58px;height:58px}.receipt-ref{grid-column:1/-1;width:100%}.school-name{font-size:1.35rem}.campus-lines{font-size:.68rem}.student-box{grid-template-columns:1fr}.date-box{grid-row:1;border-left:0;border-bottom:3px solid #101820}.student-line{grid-template-columns:120px 1fr}.fee-tables{grid-template-columns:1fr;gap:10px}.receipt-footer{flex-direction:column}.receipt-table td{padding:8px 10px;font-size:.84rem}
        }
        @page { size: A5 landscape; margin: 8mm; }
        @media print {
            body { padding: 0; background: #fff; }
            .receipt-toolbar { display: none; }
            .receipt-paper { width: 100%; padding: 0; border: 0; box-shadow: none; }
            .school-name { font-size: 2rem; }
            .receipt-heading { margin: 10px auto; }
            .student-details { padding: 12px 16px; }
            .receipt-table td { padding: 6px 12px; font-size: .82rem; }
            .receipt-message { min-height: 70px; }
        }
    </style>
</head>
<body>
    <div class="receipt-toolbar no-print">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Fee ledger</a>
        <button type="button" onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print me-2"></i>Print receipt</button>
    </div>

    <main class="receipt-paper">
        <header class="school-header">
            <img class="school-logo" src="{{ asset('images/al-suffaa-school-crest.png') }}" alt="Al Suffaa School crest">
            <div>
                <h1 class="school-name">Al Suffaa School</h1>
                <div class="campus-lines">
                    <div>Campus 1: Gulshan Town, TNB Lahore · 0300-6694291</div>
                    <div>Campus 2: 43-C Rana Town, Main GT Road · 0344-4008464</div>
                </div>
            </div>
            <div class="receipt-ref"><span>Receipt number</span><strong>{{ $payment->receipt_number }}</strong></div>
        </header>

        <div class="receipt-heading">Fee Receipt</div>

        <section class="student-box">
            <div class="student-details">
                <div class="student-line"><span>Student's Name</span><strong>{{ $payment->student->name }}</strong></div>
                <div class="student-line"><span>Father's Name</span><strong>{{ $payment->student->father_name }}</strong></div>
                <div class="student-line"><span>Class</span><strong>{{ $payment->student->class }}</strong></div>
                <div class="student-line"><span>Campus</span><strong>{{ ucfirst(str_replace('_', ' ', $payment->student->campus)) }}</strong></div>
            </div>
            <div class="date-box"><span>Date:</span><strong>{{ $payment->payment_date->format('d-m-y') }}</strong></div>
        </section>

        <section class="fee-tables">
            <table class="receipt-table" aria-label="Fee charges">
                <tbody>
                    <tr><td>Monthly Fee</td><td>{{ number_format($payment->monthly_fee_amount, 0) }}</td></tr>
                    <tr><td>Uniform</td><td>{{ number_format($payment->uniform_amount, 0) }}</td></tr>
                    <tr><td>Books</td><td>{{ number_format($payment->books_amount, 0) }}</td></tr>
                    <tr><td>Paper Fund</td><td>{{ number_format($payment->paper_fund_amount, 0) }}</td></tr>
                    <tr><td>Others</td><td>{{ number_format($payment->other_amount, 0) }}</td></tr>
                    <tr><td>Previous Balance</td><td>{{ number_format($payment->previous_balance_amount, 0) }}</td></tr>
                </tbody>
            </table>

            <div>
                <table class="receipt-table summary-table" aria-label="Payment summary">
                    <tbody>
                        <tr><td>Discount</td><td>{{ number_format($payment->discount_amount, 0) }}</td></tr>
                        <tr><td>Total</td><td>{{ number_format($payment->total_amount, 0) }}</td></tr>
                        <tr><td>Paid</td><td>{{ number_format($payment->amount, 0) }}</td></tr>
                        <tr><td>Remaining</td><td>{{ number_format($payment->remaining_amount, 0) }}</td></tr>
                    </tbody>
                </table>
                <div class="receipt-message">Computer-generated slip.<br>Signature or stamp is not required.</div>
            </div>
        </section>

        @php($paymentStatus = $payment->remaining_amount > 0 ? ($payment->amount > 0 ? 'Partially paid' : 'Payment pending') : 'Paid in full')
        <span class="receipt-status"><i class="fa-solid fa-circle-check"></i>{{ $paymentStatus }}</span>
        @if($payment->description)<p class="receipt-note"><strong>Note:</strong> {{ $payment->description }}</p>@endif
        <footer class="receipt-footer"><span>Keep this receipt for your records.</span><span>Al Suffaa School · Lahore</span></footer>
    </main>
</body>
</html>
