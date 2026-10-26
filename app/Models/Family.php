<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = ['family_code', 'display_name', 'primary_guardian_id', 'phone', 'address', 'notes'];

    protected static function booted(): void
    {
        static::created(function (Family $family): void {
            if (! $family->family_code) {
                $family->forceFill(['family_code' => 'FAM-'.str_pad((string) $family->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function primaryGuardian(): BelongsTo { return $this->belongsTo(Guardian::class, 'primary_guardian_id'); }
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)->withPivot(['relationship_role', 'is_primary', 'authorized_fee_payer'])->withTimestamps();
    }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function payments(): HasMany { return $this->hasMany(FamilyPayment::class); }
    public function statements(): HasMany { return $this->hasMany(FamilyStatement::class); }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) FeeInvoice::whereHas('student', fn ($query) => $query->where('family_id', $this->id))
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->selectRaw('COALESCE(SUM(total - paid), 0) AS balance')->value('balance');
    }
}
