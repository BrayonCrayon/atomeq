<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementShowRequest;
use App\Http\Resources\ElementResource;
use App\Models\Element;

class ElementShow extends Controller
{
    public function __invoke(ElementShowRequest $request)
    {
        $element = new ElementResource(Element::findOrFail($request->input('id')));

        //TODO: handle if element is not found - findOrFail() returns 404, create a better error response and bubble it back up

        return $element;
    }
}
