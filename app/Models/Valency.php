<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valency extends Model
{
    use HasFactory;

    protected $table = 'element_valencies';
    protected $fillable = [
        'element_id',
        'valency',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'bool'
    ];

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }
}
