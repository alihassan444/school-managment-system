<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $fillable = ['name', 'code', 'is_recurring', 'is_active'];
    protected $casts = ['is_recurring' => 'boolean', 'is_active' => 'boolean'];
}
