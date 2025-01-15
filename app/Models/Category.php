<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeWithoutEmpty($query)
    {
        return $query->whereHas('products.inventory', function ($query) {
            $query->where('quantity', '>', 0);
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function translate($column, $locale)
    {
        return $this->translations()->where('key', '=', 'category.' . $column)
            ->where('locale', '=', $locale)->value('translation');
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translateable');
    }
}
