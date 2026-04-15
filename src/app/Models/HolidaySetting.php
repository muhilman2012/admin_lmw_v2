<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidaySetting extends Model
{
    protected $fillable = ['holiday_date', 'note'];

    protected $casts = [
        'holiday_date' => 'date',
    ];
}