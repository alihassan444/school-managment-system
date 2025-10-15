<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History Export</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #4F46E5;
            padding-bottom: 20px;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .export-info {
            font-size: 12px;
            color: #888;
        }
        
        .filters-section {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #4F46E5;
        }
        
        .filters-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #4F46E5;
        }
        
        .filter-item {
            margin-bottom: 5px;
        }
        
        .filter-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #4F46E5;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .table tr:hover {
            background-color: #e9ecef;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 2px solid #4F46E5;
        }
        
        .totals-title {
            font-size: 16px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .total-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .total-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .total-value {
            font-size: 14px;
            font-weight: bold;
            color: #4F46E5;
        }
        
        .grand-total {
            grid-column: 1 / -1;
            background-color: #4F46E5;
            color: white;
            margin-top: 10px;
        }
        
        .grand-total .total-value {
            color: white;
            font-size: 18px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $schoolName }}</div>
        <div class="report-title">Payment History Report</div>
        <div class="export-info">Generated on: {{ $exportDate }}</div>
    </div>

    @if(count($filters) > 0)
        <div class="filters-section">
            <div class="filters-title">Applied Filters:</div>
            @foreach($filters as $label => $value)
                <div class="filter-item">
                    <span class="filter-label">{{ $label }}:</span>
                    <span>{{ $value }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if($payments->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Receipt</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Monthly Fee</th>
                    <th>Books</th>
                    <th>Other</th>
                    <th>Prev. Balance</th>
                    <th>Total</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                        <td>{{ $payment->student->name }}</td>
                        <td>{{ $payment->student->class }}</td>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ $payment->primary_payment_type }}</td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                        <td class="amount">Rs {{ number_format($payment->monthly_fee_amount, 0) }}</td>
                        <td class="amount">Rs {{ number_format($payment->books_amount, 0) }}</td>
                        <td class="amount">Rs {{ number_format($payment->other_amount, 0) }}</td>
                        <td class="amount">Rs {{ number_format($payment->previous_balance_amount, 0) }}</td>
                        <td class="amount">Rs {{ number_format($payment->amount, 0) }}</td>
                        <td>{{ $payment->reference_number ?: 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-title">Summary Totals</div>
            <div class="totals-grid">
                <div class="total-item">
                    <div class="total-label">Monthly Fees</div>
                    <div class="total-value">Rs {{ number_format($totalMonthlyFee, 0) }}</div>
                </div>
                <div class="total-item">
                    <div class="total-label">Books</div>
                    <div class="total-value">Rs {{ number_format($totalBooks, 0) }}</div>
                </div>
                <div class="total-item">
                    <div class="total-label">Other</div>
                    <div class="total-value">Rs {{ number_format($totalOther, 0) }}</div>
                </div>
                <div class="total-item">
                    <div class="total-label">Previous Balance</div>
                    <div class="total-value">Rs {{ number_format($totalPreviousBalance, 0) }}</div>
                </div>
                <div class="total-item grand-total">
                    <div class="total-label">Grand Total</div>
                    <div class="total-value">Rs {{ number_format($totalAmount, 0) }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="no-data">
            <h3>No Payment Records Found</h3>
            <p>No payments match the current filter criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Al-Suffaa School Learning Management System</p>
        <p>Report generated on {{ $exportDate }} | Total Records: {{ $payments->count() }}</p>
    </div>
</body>
</html>
