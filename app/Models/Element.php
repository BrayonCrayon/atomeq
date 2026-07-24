<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Element extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'atomic_number',
        'atomic_mass',
        'symbol',
        'neutrons',
        'protons',
        'electrons',
        'period',
        'group',
        'element_state_id',
        'radioactive',
        'natural',
        'metal',
        'metalloid',
        'type_id',
        'atomic_radius',
        'electronegativity',
        'first_ionization',
        'density',
        'melting_point',
        'boiling_point',
        'isotopes',
        'specific_heat',
        'shells',
        'valence',
        'is_diatomic',
        'activity_rank'
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(ElementState::class, 'element_state_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function valencies(): HasMany
    {
        return $this->hasMany(Valency::class, 'element_id', 'id');
    }

    public function discoverers(): BelongsToMany
    {
        return $this->belongsToMany(Discoverer::class, ElementDiscovery::class)->withPivot('year');
    }
}
