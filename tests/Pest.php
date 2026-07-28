<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Element;
use App\Models\Valency;

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function oxygenFactory(): Element
{
    return Element::factory()->hasValencies(1, ['valency' => 2])->create([
        'symbol' => 'O',
        'electronegativity' => 3.44
    ]);
}

function chlorineFactory(): Element
{
    return Element::factory()->hasValencies(1, ['valency' => 1])->create([
        'name' => 'Chlorine',
        'symbol' => 'Cl',
        'electronegativity' => 3.16
    ]);
}

function sulfurFactory(): Element
{
    return Element::factory()->hasValencies(1, ['valency' => 2])->create([
        'name' => 'Sulfur',
        'symbol' => 'S',
        'electronegativity' => 2.58
    ]);
}

function hydrogenFactory(): Element
{
    return Element::factory()
        ->hasValencies(1, ['valency' => 1])
        ->create([
            'name' => 'Hydrogen',
            'symbol' => 'H',
            'electronegativity' => 2.2
        ]);
}

function ironFactory(): Element
{
    return Element::factory()
        ->has(
            Valency::factory()
                ->sequence(
                    ['valency' => 2],
                    ['valency' => 3, 'is_default' => false]
                )
        )
        ->create([
            'name' => 'Iron',
            'symbol' => 'Fe',
            'electronegativity' => 1.83,
            'activity_rank' => 20,
        ]);
}

function magnesiumFactory(): Element
{
    return Element::factory()->hasValencies(1, ['valency' => 2])->create([
        'Magnesium',
        'symbol' => 'Mg',
        'electronegativity' => 1.31
    ]);
}

function copperFactory(): Element
{
    return Element::factory()
        ->has(
            Valency::factory()
                ->sequence(
                    ['valency' => 1],
                    ['valency' => 2, 'is_default' => false]
                )
        )
        ->create([
            'name' => 'Copper',
            'symbol' => 'Cu',
            'electronegativity' => 1.90,
            'activity_rank' => 5,
        ]);
}
