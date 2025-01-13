<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translateable');
    }

    public function categories()
    {
        return $this->hasManyThrough(
            Category::class,
            Product::class,
            'store_id',
            'id',
            'id',
            'category_id'
        );
    }

    public function scopeHasCategories($query, $categories)
    {
        return $query->whereHas('products', function ($query) use ($categories) {
            $query->hasCategories($categories);
        });
    }
}
