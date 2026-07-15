<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polyatomic_ions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol');
            $table->integer('charge');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polyatomic_ions');
    }
};
