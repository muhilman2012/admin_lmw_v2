<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitSlotSetting extends Model
{
    protected $fillable = ['time_start', 'quota', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}