<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('elements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('atomic_number');
            $table->decimal('atomic_mass', 10, 8);
            $table->string('symbol');

            $table->smallInteger('neutrons');
            $table->smallInteger('protons');
            $table->smallInteger('electrons');
            $table->smallInteger('period');
            $table->smallInteger('group')->nullable();

            $table->foreignId('element_state_id')->references('id')->on('element_states');

            $table->boolean('radioactive')->default(false);
            $table->boolean('natural');
            $table->boolean('metal');
            $table->boolean('metalloid');
            $table->foreignId('type_id')->nullable()->references('id')->on('types');

            $table->decimal('atomic_radius', 10, 8)->nullable();
            $table->decimal('electronegativity', 10, 8)->nullable();
            $table->decimal('first_ionization', 10, 8)->nullable();
            $table->string('density')->nullable();
            $table->decimal('melting_point', 10, 8)->nullable();
            $table->decimal('boiling_point', 10, 8)->nullable();
            $table->smallInteger('isotopes')->nullable();
            $table->smallInteger('specific_heat')->nullable();
            $table->smallInteger('shells')->nullable();
            $table->smallInteger('valence')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elements');
    }
};
