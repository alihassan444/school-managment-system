<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    protected $fillable = [
        'school_class_id', 'section_id', 'subject_id', 'staff_id', 'day_of_week',
        'starts_at', 'ends_at', 'room',
    ];
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function section() { return $this->belongsTo(Section::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function staff() { return $this->belongsTo(Staff::class); }
}
