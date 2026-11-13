<?php

use App\Models\Element;
use App\Models\MonatomicCation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monatomic_cations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->references('id')->on('elements');
            $table->smallInteger('charge');
            $table->timestamps();
        });

        $data = [
            ['element' => 'H', 'charge' => 1],
            ['element' => 'Li', 'charge' => 1],
            ['element' => 'Na', 'charge' => 1],
            ['element' => 'K', 'charge' => 1],
            ['element' => 'Rb', 'charge' => 1],
            ['element' => 'Cs', 'charge' => 1],
            ['element' => 'Be', 'charge' => 2],
            ['element' => 'Mg', 'charge' => 2],
            ['element' => 'Ca', 'charge' => 2],
            ['element' => 'Sr', 'charge' => 2],
            ['element' => 'Ba', 'charge' => 2],
            ['element' => 'Al', 'charge' => 3],
            ['element' => 'Zn', 'charge' => 2],
            ['element' => 'Ag', 'charge' => 1],
            ['element' => 'Cd', 'charge' => 2],
            ['element' => 'Fe', 'charge' => 2],
            ['element' => 'Fe', 'charge' => 3],
            ['element' => 'Cu', 'charge' => 1],
            ['element' => 'Cu', 'charge' => 2],
            ['element' => 'Ni', 'charge' => 2],
            ['element' => 'Co', 'charge' => 2],
            ['element' => 'Co', 'charge' => 3],
            ['element' => 'Cr', 'charge' => 2],
            ['element' => 'Cr', 'charge' => 3],
            ['element' => 'Mn', 'charge' => 2],
            ['element' => 'Sn', 'charge' => 2],
            ['element' => 'Sn', 'charge' => 4],
            ['element' => 'Pb', 'charge' => 2],
            ['element' => 'Pb', 'charge' => 4],
            ['element' => 'Hg', 'charge' => 1],
            ['element' => 'Hg', 'charge' => 2],
            ['element' => 'Au', 'charge' => 1],
            ['element' => 'Au', 'charge' => 3],
        ];

        foreach ($data as $item) {
            $element = Element::query()->where('symbol', $item['element'])->first();
            MonatomicCation::create([
                'element_id' => $element->id,
                'charge' => $item['charge'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monatomic_cations');
    }
};
