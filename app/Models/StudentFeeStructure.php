<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeeStructure extends Model
{
    protected $fillable = ['student_id', 'monthly_fee', 'effective_from', 'reason', 'created_by'];

    protected $casts = ['monthly_fee' => 'decimal:2', 'effective_from' => 'date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
