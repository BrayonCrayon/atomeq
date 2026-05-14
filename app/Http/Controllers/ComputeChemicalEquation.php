<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComputeChemicalEquationRequest;

class ComputeChemicalEquation extends Controller
{

    public function __invoke(ComputeChemicalEquationRequest $request)
    {
        // TODO: Implement actual functionality on computing chemical equations HERE
        return response()->json(['result' => '2H2 + O2 = 2H2O']);
    }
}
