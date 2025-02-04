<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'password',
        'phone_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'phone_verified_at' => 'datetime:Y-m-d H:i:s',
            'password' => 'hashed',
        ];
    }

    public function orderedProducts()
    {
        return $this->orders()->with('subOrders.products');
    }

    public function hasOrderedProduct($product)
    {
        return $this->orders()->whereHas('subOrders.products', function ($query) use ($product) {
            $query->where('id', '=', $product->id);
        })->exists();
    }

    public function hasRatedProduct($product)
    {
        return $this->rates()->where('product_id', '=', $product->id)->exists();
    }

    public function rate($product)
    {
        return $this->rates()->firstWhere('product_id', '=', $product->id)->pivot->rate;
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')
            ->withTimestamps();
    }

    public function rates()
    {
        return $this->belongsToMany(Product::class, 'rates')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function hasRole(Role $role)
    {
        return $this->role == $role->value;
    }
}
