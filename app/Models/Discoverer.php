<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discoverer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
    ];

    public function elements(): BelongsToMany
    {
        return $this->belongsToMany(Element::class, 'element_discoveries');
    }
}
