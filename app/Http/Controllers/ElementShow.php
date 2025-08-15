<?php

namespace App\Http\Controllers;

use App\Http\Requests\ElementShowRequest;

class ElementShow extends Controller
{
    public function __invoke(ElementShowRequest $request)
    {
        $params = $request->input('id', []);

        return [];
    }
}
