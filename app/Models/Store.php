<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'photo',
        'delivery_time',
        'user_id',
    ];

    protected $hidden = [
        'user_id',
        'user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
