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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        'is_benefit_provided',
        'classification',
        'category_id',
        'unit_kerja_id',
        'deputy_id', 
        'created_at',
        'updated_at'
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

    public function getAnalysisStatusAttribute()
    {
        // Cek assignment terbaru berdasarkan ID (asumsi ID yang lebih besar adalah yang terbaru)
        $latestAssignment = $this->assignments()
                                ->latest('id')
                                ->first();

        // Kembalikan status dari assignment tersebut, default ke 'pending' jika tidak ada assignment
        return $latestAssignment->status ?? 'pending';
    }

    /**
     * Accessor untuk mendapatkan ID pengguna yang ditugaskan (assigned_to_user_id)
     * dari assignment terbaru.
     */
    public function getAssignedToUserIdAttribute()
    {
        $latestAssignment = $this->assignments()
                                ->latest('id')
                                ->first();

        return $latestAssignment->assigned_to_id ?? null;
    }
}
