<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    protected $fillable = ['exam_subject_id', 'student_id', 'marks_obtained', 'is_absent', 'remarks'];
    protected $casts = ['marks_obtained' => 'decimal:2', 'is_absent' => 'boolean'];
    public function student() { return $this->belongsTo(Student::class); }
}
