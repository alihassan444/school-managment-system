<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceAdjustment extends Model
{
    protected $fillable = [
        'fee_invoice_id', 'fee_invoice_item_id', 'adjustment_type', 'original_amount',
        'updated_amount', 'difference_amount', 'original_values', 'updated_values',
        'reason', 'created_by',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2', 'updated_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2', 'original_values' => 'array', 'updated_values' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(FeeInvoiceItem::class, 'fee_invoice_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
