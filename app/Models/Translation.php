<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'key',
        'locale',
        'translation',
    ];

    public function translateable()
    {
        return $this->morphTo();
    }
}
