<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'name',
        'father_name',
        'class',
        'campus',
        'contact',
        'mobile',
        'address',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Removed balance fields and calculations; Student is info-only now

    // Scope for active students
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Removed formatted total/remaining accessors

    // Relationship with Payments
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Helper accessors removed to keep model minimal
}
