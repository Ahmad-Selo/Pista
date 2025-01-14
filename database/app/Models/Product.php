<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = ['rate', 'quantity'];

    protected $hidden = ['rate_sum', 'rate_count', 'updated_at', 'inventory', 'store_id', 'pivot'];

    protected function rate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) =>
            ($attributes['rate_count'] != 0 ? round($attributes['rate_sum'] / $attributes['rate_count']) : null)
        );
    }

    protected function favorite(): Attribute
    {
        $user = Auth::user();

        return Attribute::make(
            get: fn(mixed $value, array $attributes) =>
            $user->favorites()->where('product_id', $attributes['id'])->exists()
        );
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translateable');
    }

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => $this->inventory->quantity
        );
    }

    public function scopeInStock($query)
    {
        return $query->whereHas(
            'inventory',
            function ($query) {
                $query->where('quantity', '>', 0);
            }
        );
    }

    public function scopeDiscounts($query)
    {
        return $query->whereHas('offer', function ($query) {
            $query->where('discount', '>', 0);
        });
    }

    public function scopeFullPrices($query)
    {
        return $query->whereDoesntHave('offer', function ($query) {
            $query->where('discount', '>', 0);
        });
    }

    public function scopeHasCategories($query, $categories)
    {
        return $query->whereIn('category_id', $categories);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function offer()
    {
        return $this->hasOne(Offer::class);
    }

    public function updateOrCreateOffer($validated)
    {
        if ($this->offer) {
            return $this->offer->update($validated);
        }

        return $this->offer()->create($validated);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function rates()
    {
        return $this->belongsToMany(User::class, 'rates')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function subOrders()
    {
        return $this->belongsToMany(SubOrder::class, 'product_sub_order')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
