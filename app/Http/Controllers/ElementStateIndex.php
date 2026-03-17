<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementStateResource;
use App\Models\ElementState;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ElementStateIndex extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $states = ElementState::all();

        return ElementStateResource::collection($states);
    }
}
