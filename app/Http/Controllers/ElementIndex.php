<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementResource;
use App\Models\Element;

class ElementIndex extends Controller
{
    public function __invoke()
    {
        $elements = Element::all();

        return ElementResource::collection($elements);
    }
}
