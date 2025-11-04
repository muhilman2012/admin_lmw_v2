<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanForwarding extends Model
{
    use HasFactory;

    protected $table = 'laporan_forwardings';

    protected $fillable = [
        'laporan_id',
        'user_id',
        'institution_id',
        'reason',
        'status',
        'complaint_id',
        'lapor_status_code',
        'lapor_status_name',
        'disposition_name',
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

    /**
     * Relasi ke model User yang meneruskan laporan (Distributor).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id'); 
    }

    /**
     * Relasi One-to-One ke DisposisiLapor (Instansi tujuan disposisi).
     */
    public function disposisi(): HasOne
    {
        return $this->hasOne(\App\Models\DisposisiLapor::class, 'laporan_forwarding_id');
    }
}
