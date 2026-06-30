<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFreeSchool extends Model
{
    use HasFactory;

    protected $table = 'master_free_schools';
    
    protected $fillable = [
        'school_name',
        'region'
    ];
}