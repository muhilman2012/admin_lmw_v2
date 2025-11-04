<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deputy extends Model
{
    use HasFactory;

    protected $table = 'deputies';

    protected $fillable = [
        'name',
    ];

    public function unitKerjas(): HasMany
    {
        return $this->hasMany(UnitKerja::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categoriesThroughUnits()
    {
        return Category::whereHas('unitKerjas', function ($query) {
            $query->whereIn('unit_kerjas.deputy_id', [$this->id]);
        })->get();
    }
}
