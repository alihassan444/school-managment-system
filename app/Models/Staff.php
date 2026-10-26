<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'employee_no', 'name', 'role', 'department', 'phone', 'email', 'joined_on',
        'basic_salary', 'status', 'address',
    ];

    protected $casts = ['joined_on' => 'date', 'basic_salary' => 'decimal:2'];

    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }
}
