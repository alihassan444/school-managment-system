<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiblingDiscountRule extends Model
{
    protected $fillable = [
        'name', 'discount_type', 'discount_value', 'minimum_sibling_order',
        'fee_category_id', 'is_active', 'priority', 'notes',
    ];
    protected $casts = ['discount_value' => 'decimal:2', 'is_active' => 'boolean'];
    public function feeCategory(): BelongsTo { return $this->belongsTo(FeeCategory::class); }
}
