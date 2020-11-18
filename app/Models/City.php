<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    // protected $guards = [];

    public function dists()
    {
        return $this->hasMany('App\Models\Dist');
    }
}
