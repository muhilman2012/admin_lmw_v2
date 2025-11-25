<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status_code',
        'response_template',
    ];
}
