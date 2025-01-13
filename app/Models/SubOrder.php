<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubOrder extends Model
{
    protected $fillable = [
        'price',
        'state',
        'order_id',
        'store_id'
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class,'product_sub_order')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function stores()
    {
        return $this->belongsTo(Store::class);
    }
}
