<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElementDiscovery extends Model
{
    use HasFactory;

    protected $table = 'element_discoveries';

    protected $fillable = [
        'element_id',
        'discoverer_id',
        'year',
    ];
}
