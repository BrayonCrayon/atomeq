<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ComputeChemicalEquationRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'equation' => 'required|string'
        ];
    }
}
