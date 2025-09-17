<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
