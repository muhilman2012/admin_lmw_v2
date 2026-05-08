<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class RegistrationQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'registration_number',
        'queue_number',
        'nik',
        'name',
        'phone',
        'email',
        'address',
        'subject',
        'is_disabled',
        'companion_name',
        'visit_date',
        'visit_time',
        'qr_path',
        'status',
        'counter_number',
        'operator_id',
        'called_at',
        'served_at',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'visit_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}