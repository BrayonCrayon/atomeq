<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonatomicCation extends Model
{
    protected $fillable = [
        'element_id',
        'charge'
    ];

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }
}
