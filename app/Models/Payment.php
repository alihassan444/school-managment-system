<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'amount',
        'description',
        'payment_date',
        'receipt_number'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with Student
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Generate receipt number
    public static function generateReceiptNumber()
    {
        $lastPayment = self::orderBy('id', 'desc')->first();
        $lastNumber = $lastPayment ? (int) substr($lastPayment->receipt_number, 3) : 0;
        return 'RCP' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    // Get formatted amount
    public function getFormattedAmountAttribute()
    {
        return 'Rs ' . number_format($this->amount, 0);
    }

    // Legacy accessors removed: payment method, breakdown, and type are no longer used
}
