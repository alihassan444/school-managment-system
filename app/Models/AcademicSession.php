<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_current'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_current' => 'boolean'];

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
