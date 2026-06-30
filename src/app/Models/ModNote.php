<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModNote extends Model
{
    protected $fillable = [
        'report_id', 
        'actual_user_id', 
        'note', 
        'attachment_path',
        'attachment_name',
        'created_at', 
        'updated_at'
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function actualUser()
    {
        return $this->belongsTo(User::class, 'actual_user_id');
    }
}
