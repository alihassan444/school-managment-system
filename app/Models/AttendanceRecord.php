<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = ['student_id', 'attendance_date', 'status', 'remarks', 'marked_by'];
    protected $casts = ['attendance_date' => 'date'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
