<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvinceMapId extends Model
{
    use HasFactory;

    protected $fillable = ['bps_code', 'map_id', 'name'];
}
