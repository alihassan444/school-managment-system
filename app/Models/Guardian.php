<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    protected $fillable = [
        'name', 'relation', 'phone', 'normalized_phone', 'alternate_phone', 'cnic', 'occupation', 'email', 'address',
    ];

    protected static function booted(): void
    {
        static::saving(function (Guardian $guardian): void {
            $digits = preg_replace('/\D+/', '', (string) $guardian->phone);
            if (str_starts_with($digits, '92')) $digits = '0'.substr($digits, 2);
            $guardian->normalized_phone = $digits ?: null;
        });
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)->withPivot(['relationship_role', 'is_primary', 'authorized_fee_payer'])->withTimestamps();
    }

    public function assignedStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian')->withPivot(['relationship_role', 'is_primary'])->withTimestamps();
    }
}
