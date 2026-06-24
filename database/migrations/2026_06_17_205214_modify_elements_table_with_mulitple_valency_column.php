<?php

use App\Models\Element;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_valencies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Element::class)->index();
            $table->smallInteger('valency');
            $table->timestamps();
        });

        $existingValencyRows = DB::table('elements')
            ->select(['id','valency'])
            ->whereNotNull('valency')
            ->get();

        $existingValencyRows->each(function ($row) {
            DB::table('element_valencies')->insert([
                'element_id' => $row->id,
                'valency' => $row->valency,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_valencies');
    }
};
