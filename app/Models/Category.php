<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Relasi Many-to-Many ke UnitKerja.
     * Menggunakan tabel pivot 'category_unit'.
     */
    public function unitKerjas(): BelongsToMany
    {
        return $this->belongsToMany(UnitKerja::class, 'category_unit', 'category_id', 'unit_kerja_id')
                    ->withTimestamps(); // PERBAIKAN: Menandakan adanya created_at dan updated_at
    }

    public function scopeMainCategories($query)
    {
        return $query->whereNull('parent_id');
    }
}
