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

    protected $hidden = [
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
}
