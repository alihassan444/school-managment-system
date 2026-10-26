<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRecord extends Model
{
    protected $fillable = [
        'staff_id', 'salary_month', 'basic_salary', 'allowances', 'deductions',
        'net_salary', 'paid_on', 'status', 'reference_number',
    ];
    protected $casts = [
        'salary_month' => 'date', 'paid_on' => 'date', 'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2', 'deductions' => 'decimal:2', 'net_salary' => 'decimal:2',
    ];
    public function staff() { return $this->belongsTo(Staff::class); }
}
