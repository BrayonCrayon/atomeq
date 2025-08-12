<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'atomicNumber' => $this->resource['atomic_number'],
            'atomicMass' => $this->resource['atomic_mass'],
            'symbol' => $this->resource['symbol'],
            'neutrons' => $this->resource['neutrons'],
            'protons' => $this->resource['protons'],
            'electrons' => $this->resource['electrons'],
            'period' => $this->resource['period'],
            'group' => $this->resource['group'],
            'elementStateId' => $this->resource['element_state_id'],
            'radioactive' => $this->resource['radioactive'],
            'natural' => $this->resource['natural'],
            'metal' => $this->resource['metal'],
            'metalloid' => $this->resource['metalloid'],
            'typeId' => $this->resource['type_id'],
            'atomicRadius' => $this->resource['atomic_radius'],
            'electronegativity' => $this->resource['electronegativity'],
            'firstIonization' => $this->resource['first_ionization'],
            'density' => $this->resource['density'],
            'meltingPoint' => $this->resource['melting_point'],
            'boilingPoint' => $this->resource['boiling_point'],
            'isotopes' => $this->resource['isotopes'],
            'specificHeat' => $this->resource['specific_heat'],
            'shells' => $this->resource['shells'],
            'valence' => $this->resource['valence'],
        ];
    }
}
