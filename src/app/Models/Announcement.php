<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    // Tambahkan baris di bawah ini
    protected $fillable = [
        'title',
        'content',
        'image_path',
        'start_date',
        'end_date',
        'is_active',
    ];

    // Opsional: Agar start_date dan end_date otomatis menjadi objek Carbon
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
}