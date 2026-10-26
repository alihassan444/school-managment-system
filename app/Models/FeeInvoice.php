<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    protected $fillable = [
        'student_id', 'family_id', 'academic_session_id', 'sibling_discount_rule_id', 'invoice_number', 'billing_month', 'issued_on', 'due_date',
        'subtotal', 'discount', 'sibling_discount', 'total', 'paid', 'status', 'notes',
    ];

    protected $casts = [
        'billing_month' => 'date', 'issued_on' => 'date', 'due_date' => 'date', 'subtotal' => 'decimal:2',
        'discount' => 'decimal:2', 'sibling_discount' => 'decimal:2', 'total' => 'decimal:2', 'paid' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $invoice): void {
            if (! $invoice->family_id && $invoice->student_id) {
                $invoice->family_id = Student::whereKey($invoice->student_id)->value('family_id');
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class)->where('status', 'active');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(FeeInvoiceAdjustment::class);
    }

    public function siblingDiscountRule(): BelongsTo
    {
        return $this->belongsTo(SiblingDiscountRule::class);
    }

    public function refreshPaymentStatus(): void
    {
        $this->recalculateTotals();
    }

    public function recalculateTotals(): void
    {
        $hasLineItems = $this->items()->exists();
        $subtotalCents = $hasLineItems
            ? $this->activeItems()->get()->sum(fn ($item) => self::toCents($item->amount))
            : self::toCents($this->subtotal ?: $this->total);
        $discountCents = self::toCents($this->discount);
        $totalCents = $hasLineItems
            ? max($subtotalCents - $discountCents, 0)
            : self::toCents($this->total);
        $paidCents = $this->payments()->where('status', 'completed')->get()->sum(fn ($payment) => self::toCents($payment->amount));
        $balanceCents = max($totalCents - $paidCents, 0);
        $status = $balanceCents === 0 ? 'paid' : ($paidCents > 0 ? 'partial' : 'unpaid');
        if ($balanceCents > 0 && $this->due_date?->isPast()) {
            $status = 'overdue';
        }

        $this->forceFill([
            'subtotal' => self::fromCents($subtotalCents),
            'total' => self::fromCents($totalCents),
            'paid' => self::fromCents($paidCents),
            'status' => $status,
        ])->save();
    }

    public static function toCents(mixed $amount): int
    {
        $value = trim((string) $amount);
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad(preg_replace('/\D/', '', $fraction), 3, '0');
        $cents = ((int) preg_replace('/\D/', '', $whole) * 100) + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $cents++;
        }

        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
