<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valency extends Model
{
    protected $table = 'element_valencies';
    protected $fillable = ['element_id','valency'];

   public function element(): BelongsTo
   {
       return $this->belongsTo(Element::class);
   }
}
