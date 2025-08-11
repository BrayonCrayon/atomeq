<?php

namespace App\Http\Controllers;

use App\Models\Element;
use Illuminate\Http\Request;

class ElementIndex extends Controller
{
    public function __invoke()
    {
        $elements = Element::all();

        return [
            'data' => $elements
        ];
    }
}
