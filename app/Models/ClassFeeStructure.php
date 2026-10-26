<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassFeeStructure extends Model
{
    protected $fillable = ['school_class_id', 'monthly_fee', 'effective_from', 'reason', 'created_by'];

    protected $casts = ['monthly_fee' => 'decimal:2', 'effective_from' => 'date'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
