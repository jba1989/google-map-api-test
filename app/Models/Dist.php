<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dist extends Model
{
    use HasFactory;

    // protected $guards = [];

    public function cities()
    {
        return $this->belongsTo('App\Models\City');
    }

    public function roads()
    {
        return $this->hasMany('App\Models\Road');
    }
}
