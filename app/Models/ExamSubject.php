<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSubject extends Model
{
    protected $fillable = ['exam_id', 'subject_id', 'exam_date', 'starts_at', 'ends_at', 'max_marks', 'pass_marks'];
    protected $casts = ['exam_date' => 'date'];
    public function exam() { return $this->belongsTo(Exam::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function marks() { return $this->hasMany(ExamMark::class); }
}
