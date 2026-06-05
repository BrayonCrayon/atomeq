<?php

namespace App\ChemicalEvaluator\Enums;

enum Reactions: string
{
    case NET_FORWARD_REACTION = 'net_forward_reaction';
    case LIMITED_REACTION = 'limited_reaction';
    case EQUILIBRIUM_REACTION = 'equilibrium_reaction';
    case STOICHIOMETRIC_REACTION = 'stoichiometric_reaction';
    case RESONANCE_REACTION = 'resonance_reaction';
}
