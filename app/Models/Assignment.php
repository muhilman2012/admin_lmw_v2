<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'assigned_by_id',
        'assigned_to_id',
        'status',
        'notes',
        'analyst_worksheet',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke model Report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * Relasi ke model User (yang menugaskan).
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /**
     * Relasi ke model User (yang ditugaskan).
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    protected static function booted()
    {
        // Event ini dipicu sebelum record assignment dihapus
        static::deleting(function ($assignment) {
            
            \App\Models\ActivityLog::where('loggable_type', self::class)
                                   ->where('loggable_id', $assignment->id)
                                   ->delete();
        });
    }
}
