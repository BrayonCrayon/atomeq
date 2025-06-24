<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('element_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->references('id')->on('elements');
            $table->foreignId('discoverer_id')->nullable()->references('id')->on('discoverers');
            $table->smallInteger('year')->nullable();

            $table->index(['element_id', 'discoverer_id', 'year']);
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('element_discoveries');
    }
};
