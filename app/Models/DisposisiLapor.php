<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisposisiLapor extends Model
{
    use HasFactory;

    protected $table = 'disposisi_lapor';

    protected $fillable = [
        'laporan_forwarding_id',
        'institution_id',
        'institution_name',
    ];
    
    // Relasi ke LaporanForwarding
    public function forwarding()
    {
        return $this->belongsTo(LaporanForwarding::class);
    }
}
