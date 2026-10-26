<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'name',
        'admission_no',
        'roll_no',
        'father_name',
        'date_of_birth',
        'gender',
        'admission_date',
        'class',
        'campus',
        'academic_session_id',
        'school_class_id',
        'section_id',
        'guardian_id',
        'family_id',
        'sibling_order',
        'contact',
        'mobile',
        'address',
        'status',
        'monthly_fee',
        'photo_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'monthly_fee' => 'decimal:2',
        'date_of_birth' => 'date',
        'admission_date' => 'date',
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

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function family(): BelongsTo { return $this->belongsTo(Family::class); }
    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')->withPivot(['relationship_role', 'is_primary'])->withTimestamps();
    }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function feeInvoices(): HasMany { return $this->hasMany(FeeInvoice::class); }
    public function feeStructures(): HasMany { return $this->hasMany(StudentFeeStructure::class); }
    public function attendanceRecords(): HasMany { return $this->hasMany(AttendanceRecord::class); }

    // Helper accessors removed to keep model minimal
}
