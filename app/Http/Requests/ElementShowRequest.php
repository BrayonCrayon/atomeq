<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElementShowRequest extends FormRequest
{
    //TODO: set to true to let everyone through for now
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required',
        ];
    }
}
