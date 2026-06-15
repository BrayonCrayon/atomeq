<?php

use App\ChemicalEvaluator\ChemicalHelpers;
use App\Models\Element;

uses(ChemicalHelpers::class);

test('can lookup valency of Nitrogen', function () {
    Element::factory()->create([
        'name' => 'Nitrogen',
        'symbol' => 'N',
        'valency' => 3
    ]);
    $result = $this->valencyLookup('N');
    expect($result)->toBe(3);
});
