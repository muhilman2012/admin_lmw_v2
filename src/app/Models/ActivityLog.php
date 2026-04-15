<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'loggable_id',
        'loggable_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}