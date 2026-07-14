<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    const HNFOIClBr = [
        'H', 'N', 'F', 'O', 'I', 'Cl', 'Br',
    ];

    public function up(): void
    {
        DB::table('elements')
            ->whereIn('symbol', self::HNFOIClBr)
            ->update(['is_diatomic' => true]);
    }
};
