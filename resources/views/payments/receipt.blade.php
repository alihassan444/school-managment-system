<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .container { max-width: none; }
            @page { size: A4; margin: 12mm; }
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #667eea;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .school-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        
        .school-name {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .campus-info {
            font-size: 0.9rem;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .receipt-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px dashed #dee2e6;
        }
        
        .receipt-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .receipt-info {
            padding: 30px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 150px;
        }
        
        .info-value {
            color: #333;
            text-align: right;
        }
        
        .amount-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        
        .amount-paid {
            font-size: 2.5rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .amount-words {
            font-style: italic;
            color: #666;
            font-size: 1.1rem;
        }
        
        .thank-you {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 30px;
            border-radius: 8px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6rem;
            color: rgba(102, 126, 234, 0.1);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .signature-box {
            text-align: center;
            min-width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="{{ route('students.show', $payment->student) }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Student
            </a>
        </div>

        <div class="receipt-container position-relative">
            <!-- Watermark -->
            <div class="watermark">PAID</div>
            
            <!-- School Header -->
            <div class="school-header">
                <div class="school-name">Al Suffaa School</div>
                <div class="campus-info">
                    <div><strong>Campus 1:</strong> Gulshan Town Thokar Niaz Baig Lahore - 0300-6694291</div>
                    <div><strong>Campus 2:</strong> 43 C, Rana Town Main Gt Road - 0344-4008464</div>
                </div>
            </div>

            <!-- Receipt Header -->
            <div class="receipt-header">
                <div class="receipt-title">PAYMENT RECEIPT</div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Receipt No:</strong> {{ $payment->receipt_number }}
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong>Date:</strong> {{ $payment->payment_date->format('d M Y') }}
                    </div>
                </div>
            </div>

            <!-- Receipt Information -->
            <div class="receipt-info">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">Student Details</h5>
                        <div class="info-row">
                            <span class="info-label">Student Name:</span>
                            <span class="info-value">{{ $payment->student->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Father's Name:</span>
                            <span class="info-value">{{ $payment->student->father_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <span class="info-value">{{ $payment->student->class }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Student ID:</span>
                            <span class="info-value">{{ str_pad($payment->student->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">Payment Details</h5>
                        <div class="info-row">
                            <span class="info-label">Description:</span>
                            <span class="info-value">{{ $payment->description ?: 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Breakdown removed in simplified flow -->

                <!-- Amount Section -->
                <div class="amount-section">
                    <div class="amount-paid">Rs {{ number_format($payment->amount, 0) }}</div>
                    <div class="amount-words">
                        {{ ucwords(numberToWords($payment->amount)) }} Only
                    </div>
                </div>

                @if($payment->description)
                <div class="alert alert-info">
                    <strong>Note:</strong> {{ $payment->description }}
                </div>
                @endif

                <!-- Balance info removed in simplified system -->

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <small>Student/Parent Signature</small>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <small>Received By</small>
                    </div>
                </div>
            </div>

            <!-- Thank You Section -->
            <div class="thank-you">
                <h5 class="text-primary mb-2">Thank You for Your Payment!</h5>
                <p class="mb-0">This receipt is computer generated and valid without signature.</p>
                <small class="text-muted">For queries, contact school office during working hours.</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

@php
function numberToWords($number) {
    $ones = array(
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
        16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen'
    );
    
    $tens = array(
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
    );
    
    if ($number < 20) {
        return $ones[$number];
    } elseif ($number < 100) {
        return $tens[intval($number / 10)] . ($number % 10 ? ' ' . $ones[$number % 10] : '');
    } elseif ($number < 1000) {
        return $ones[intval($number / 100)] . ' hundred' . ($number % 100 ? ' ' . numberToWords($number % 100) : '');
    } elseif ($number < 1000000) {
        return numberToWords(intval($number / 1000)) . ' thousand' . ($number % 1000 ? ' ' . numberToWords($number % 1000) : '');
    } else {
        return numberToWords(intval($number / 1000000)) . ' million' . ($number % 1000000 ? ' ' . numberToWords($number % 1000000) : '');
    }
}
@endphp
