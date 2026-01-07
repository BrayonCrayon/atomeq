<?php

namespace App\Http\Controllers;

use App\Http\Resources\TypeResource;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeIndex extends Controller
{
    public function __invoke(Request $request)
    {
        $types = Type::query()->get();

        return TypeResource::collection($types);
    }
}
