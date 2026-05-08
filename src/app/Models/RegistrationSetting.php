<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSetting extends Model
{
    protected $fillable = ['open_date', 'close_date', 'eligibility_days', 'is_active'];

    protected $casts = [
        'open_date' => 'date',
        'close_date' => 'date',
        'is_active' => 'boolean',
    ];
}