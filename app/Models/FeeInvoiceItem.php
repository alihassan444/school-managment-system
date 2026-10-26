<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    protected $fillable = [
        'fee_invoice_id', 'fee_category_id', 'description', 'charge_date', 'billing_month',
        'item_type', 'status', 'source_item_id', 'amount', 'created_by', 'reversed_by',
        'reversed_at', 'reversal_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'charge_date' => 'date', 'billing_month' => 'date', 'reversed_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function category()
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceItem()
    {
        return $this->belongsTo(self::class, 'source_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
