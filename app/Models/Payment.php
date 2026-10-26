<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'fee_invoice_id',
        'family_payment_id',
        'idempotency_key',
        'recorded_by',
        'amount',
        'payment_method',
        'reference_number',
        'status',
        'monthly_fee_amount',
        'uniform_amount',
        'books_amount',
        'paper_fund_amount',
        'other_amount',
        'previous_balance_amount',
        'discount_amount',
        'total_amount',
        'remaining_amount',
        'description',
        'payment_date',
        'posted_at',
        'receipt_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'monthly_fee_amount' => 'decimal:2',
        'uniform_amount' => 'decimal:2',
        'books_amount' => 'decimal:2',
        'paper_fund_amount' => 'decimal:2',
        'other_amount' => 'decimal:2',
        'previous_balance_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'payment_date' => 'date',
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Relationship with Student
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function familyPayment(): BelongsTo
    {
        return $this->belongsTo(FamilyPayment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Generate receipt number
    public static function generateReceiptNumber()
    {
        do {
            $receipt = 'RCP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (self::where('receipt_number', $receipt)->exists());

        return $receipt;
    }

    // Get formatted amount
    public function getFormattedAmountAttribute()
    {
        return 'Rs '.number_format($this->amount, 0);
    }

    // Legacy accessors removed: payment method, breakdown, and type are no longer used
}
