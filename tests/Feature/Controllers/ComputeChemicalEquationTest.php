<?php

describe('Compute Chemical Equation', function () {
   test('will compute the chemical equation', function () {
      $equation = "<p>H<sub>2</sub></p> + <p>O<sub>2</sub></p> =";

      $this->postJson(route('compute-chemical-equation'), ['equation' => $equation])
          ->assertOk()
          ->assertExactJson([
              'result' => '2H2 + O2 = 2H2O'
          ]);
   });

   test('will reject request when equation is not present', function () {
       $this->postJson(route('compute-chemical-equation'))
           ->assertUnprocessable();
   });

    test('will reject request when equation is empty', function () {
        $this->postJson(route('compute-chemical-equation'), ['equation' => ''])
            ->assertUnprocessable();
    });
});
