<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyStatementItem extends Model
{
    protected $fillable = ['family_statement_id', 'fee_invoice_id', 'balance_at_issue'];
    protected $casts = ['balance_at_issue' => 'decimal:2'];
    public function statement(): BelongsTo { return $this->belongsTo(FamilyStatement::class, 'family_statement_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); }
}
