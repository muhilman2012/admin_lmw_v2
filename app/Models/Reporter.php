<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'nik',
        'kk_number',
        'gender',
        'address',
        'ktp_document_id',
        'checkin_status',
    ];

    public function ktpDocument()
    {
        return $this->belongsTo(Document::class, 'ktp_document_id');
    }
}
