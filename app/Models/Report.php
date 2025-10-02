<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\UnitKerja;
use App\Models\Deputy;

class Report extends Model
{
    use HasFactory;

    protected $casts = [
        'event_date' => 'date',
    ];

    protected $fillable = [
        'ticket_number',
        'uuid',
        'lapor_complaint_id',
        'reporter_id',
        'subject',
        'details',
        'location',
        'event_date',
        'source',
        'status',
        'response',
        'classification',
        'category_id',
        'unit_kerja_id',
        'deputy_id', 
    ];

    public function reporter()
    {
        return $this->belongsTo(Reporter::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function deputy(): BelongsTo
    {
        return $this->belongsTo(Deputy::class, 'deputy_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'report_id');
    }

    public function getParentCategoryNameAttribute()
    {
        // Cek jika category yang dipilih memiliki parent (berarti dia adalah sub-kategori)
        if ($this->category && $this->category->parent_id) {
            return $this->category->parent->name ?? null;
        }
        
        // Jika tidak punya parent, atau kategori tidak terisi
        return null;
    }
}
