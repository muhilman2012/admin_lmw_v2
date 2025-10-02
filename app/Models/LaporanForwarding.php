<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanForwarding extends Model
{
    use HasFactory;

    protected $table = 'laporan_forwardings';

    protected $fillable = [
        'laporan_id',
        'institution_id',
        'reason',
        'status',
        'complaint_id',
        'lapor_status_code',
        'lapor_status_name',
        'is_anonymous',
        'error_message',
        'sent_at',
        'scheduled_at',
        'next_check_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'next_check_at' => 'datetime',
    ];

    public function laporan()
    {
        return $this->belongsTo(Report::class, 'laporan_id', 'id'); 
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
