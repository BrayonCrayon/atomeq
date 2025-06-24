<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];
}
