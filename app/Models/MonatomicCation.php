<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonatomicCation extends Model
{
    protected $fillable = [
        'element_id',
        'charge'
    ];
}
