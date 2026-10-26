<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id', 'academic_session_id', 'school_class_id', 'section_id',
        'roll_no', 'status', 'enrolled_on',
    ];

    protected $casts = ['enrolled_on' => 'date'];

    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function section(): BelongsTo { return $this->belongsTo(Section::class); }
}
