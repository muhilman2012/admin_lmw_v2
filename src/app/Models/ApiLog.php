<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'endpoint',
        'method',
        'payload',
        'response_code',
        'response_body',
        'ip_address',
        'reference_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_body' => 'array',
    ];
}