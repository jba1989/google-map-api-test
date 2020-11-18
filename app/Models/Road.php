<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Road extends Model
{
    use HasFactory;

    // protected $guards = [];

    public function dist()
    {
        return $this->belongsTo('App\Models\Dist');
    }
}
