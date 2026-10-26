<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyPayment extends Model
{
    protected $fillable = [
        'family_id', 'billing_month', 'payment_scope', 'receipt_number', 'idempotency_key', 'total_amount', 'payment_method',
        'reference_number', 'payment_date', 'received_by', 'status', 'notes', 'reversed_at', 'reversed_by',
    ];

    protected $casts = ['billing_month' => 'date', 'total_amount' => 'decimal:2', 'payment_date' => 'date', 'reversed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::created(function (FamilyPayment $payment): void {
            if (! $payment->receipt_number) {
                $payment->forceFill(['receipt_number' => 'FR-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function family(): BelongsTo { return $this->belongsTo(Family::class); }
    public function collector(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function allocations(): HasMany { return $this->hasMany(FamilyPaymentAllocation::class); }
}
