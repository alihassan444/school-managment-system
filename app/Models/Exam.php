<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['academic_session_id', 'school_class_id', 'name', 'starts_on', 'ends_on', 'status'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function subjects() { return $this->hasMany(ExamSubject::class); }
}
