<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable=[
        'user_id',
        'price',
        'state',
        'total_sub_orders',
        'completed_sub_orders',
        'delivery_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function subOrders(){
        return $this->hasMany(SubOrder::class);
    }
}
