<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'retrieval_time',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
