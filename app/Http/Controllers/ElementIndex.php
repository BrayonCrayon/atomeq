<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementResource;
use App\Models\Element;
use Illuminate\Http\Request;

class ElementIndex extends Controller
{
    public function __invoke(Request $request)
    {
        $relations = $request->input('relations', []);

        $elements = Element::with($relations)->get();

        return ElementResource::collection($elements);
    }
}
