<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyStatement extends Model
{
    protected $fillable = ['family_id', 'statement_number', 'billing_month', 'issue_date', 'due_date', 'total_amount', 'created_by'];
    protected $casts = ['billing_month' => 'date', 'issue_date' => 'date', 'due_date' => 'date', 'total_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::created(function (FamilyStatement $statement): void {
            if (! $statement->statement_number) {
                $statement->forceFill(['statement_number' => 'FS-'.str_pad((string) $statement->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function family(): BelongsTo { return $this->belongsTo(Family::class); }
    public function items(): HasMany { return $this->hasMany(FamilyStatementItem::class); }
}
