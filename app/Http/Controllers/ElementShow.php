<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementResource;
use App\Models\Element;
use Illuminate\Http\Request;

class ElementShow extends Controller
{
    public function __invoke(Request $request, Element $element): ElementResource
    {
        $relations = $request->input('relations', []);

        $element->load($relations);

        return new ElementResource($element);
    }
}
