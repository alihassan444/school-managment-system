<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyPaymentAllocation extends Model
{
    protected $fillable = ['family_payment_id', 'payment_id', 'student_id', 'fee_invoice_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function familyPayment(): BelongsTo { return $this->belongsTo(FamilyPayment::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); }
}
