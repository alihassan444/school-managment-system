<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Bill - {{ $student->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .container { max-width: none; }
        }
        
        .school-header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .school-name {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .campus-info {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.6;
        }
        
        .bill-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .fee-table {
            margin-bottom: 30px;
        }
        
        .fee-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .outstanding-amount {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .payment-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .footer-note {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 0.9rem;
            color: #666;
        }
        
        .bill-number {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Bill
            </button>
            <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Student
            </a>
        </div>

        <!-- Bill Number -->
        <div class="bill-number no-print">
            Bill #{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}
        </div>

        <!-- School Header -->
        <div class="school-header">
            <div class="school-name">Al Suffaa School</div>
            <div class="campus-info">
                <div><strong>Campus 1:</strong> Gulshan Town Thokar Niaz Baig Lahore - 0300-6694291</div>
                <div><strong>Campus 2:</strong> 43 C, Rana Town Main Gt Road - 0344-4008464</div>
            </div>
        </div>

        <!-- Bill Header -->
        <div class="bill-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-0">Fee Bill</h3>
                    <p class="mb-0">Academic Session: {{ date('Y') }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0"><strong>Date:</strong> {{ date('d M Y') }}</p>
                    <p class="mb-0"><strong>Bill No:</strong> ASS{{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary mb-3">Student Information</h5>
                    <p><strong>Name:</strong> {{ $student->name }}</p>
                    <p><strong>Father's Name:</strong> {{ $student->father_name }}</p>
                    <p><strong>Class:</strong> {{ $student->class }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-primary mb-3">Contact Information</h5>
                    <p><strong>Contact:</strong> {{ $student->contact ?: 'N/A' }}</p>
                    <p><strong>Mobile:</strong> {{ $student->mobile ?: 'N/A' }}</p>
                    <p><strong>Student ID:</strong> {{ str_pad($student->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </div>

        <!-- Fee Details Table -->
        <div class="fee-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Monthly Tuition Fee</td>
                        <td class="text-end">{{ number_format($student->fees, 0) }}</td>
                    </tr>
                    @if($student->books > 0)
                    <tr>
                        <td>Books & Stationery</td>
                        <td class="text-end">{{ number_format($student->books, 0) }}</td>
                    </tr>
                    @endif
                    @if($student->other > 0)
                    <tr>
                        <td>Other Charges</td>
                        <td class="text-end">{{ number_format($student->other, 0) }}</td>
                    </tr>
                    @endif
                    @if($student->previous_balance > 0)
                    <tr>
                        <td>Previous Balance</td>
                        <td class="text-end">{{ number_format($student->previous_balance, 0) }}</td>
                    </tr>
                    @endif
                    @if($student->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td class="text-end text-success">-{{ number_format($student->discount, 0) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>Total Amount</strong></td>
                        <td class="text-end"><strong>{{ number_format($student->total, 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Amount Paid</td>
                        <td class="text-end text-success">{{ number_format($student->paid, 0) }}</td>
                    </tr>
                    <tr class="table-warning">
                        <td><strong>Outstanding Balance</strong></td>
                        <td class="text-end"><strong class="{{ $student->remaining > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($student->remaining, 0) }}
                        </strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($student->remaining > 0)
        <!-- Outstanding Amount Alert -->
        <div class="outstanding-amount">
            <h4 class="text-warning mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i>Outstanding Amount
            </h4>
            <h2 class="text-danger mb-0">Rs {{ number_format($student->remaining, 0) }}</h2>
            <p class="mb-0 mt-2">Please pay the outstanding amount at your earliest convenience.</p>
        </div>
        @else
        <!-- Paid in Full -->
        <div class="alert alert-success text-center">
            <h4 class="text-success mb-2">
                <i class="fas fa-check-circle me-2"></i>All Fees Paid
            </h4>
            <p class="mb-0">Thank you! All fees have been paid in full.</p>
        </div>
        @endif

        <!-- Payment History -->
        @if($student->payments->count() > 0)
        <div class="payment-history mt-4">
            <h5 class="text-primary mb-3">Recent Payment History</h5>
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No.</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->payments->take(10) as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ $payment->payment_type_display }} - {{ $payment->month_year }}</td>
                        <td>{{ $payment->payment_method_display }}</td>
                        <td class="text-end">Rs {{ number_format($payment->amount, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Payment Information -->
        <div class="payment-info">
            <h5 class="text-primary mb-3">Payment Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Bank Details:</strong></p>
                    <p class="mb-1">Account Title: Al Suffaa School</p>
                    <p class="mb-1">Bank: [Bank Name]</p>
                    <p class="mb-1">Account No: [Account Number]</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Office Hours:</strong></p>
                    <p class="mb-1">Monday - Friday: 8:00 AM - 4:00 PM</p>
                    <p class="mb-1">Saturday: 8:00 AM - 2:00 PM</p>
                    <p class="mb-1">Sunday: Closed</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note text-center">
            <p><strong>Note:</strong> This is a computer-generated fee bill. Please keep this for your records.</p>
            <p>For any queries, please contact the school office during working hours.</p>
            <p class="mt-3"><em>Al Suffaa School - Excellence in Education</em></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
