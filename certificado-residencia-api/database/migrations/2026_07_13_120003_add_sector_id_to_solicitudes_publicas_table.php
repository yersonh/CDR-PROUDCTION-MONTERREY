<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->foreignId('sector_id')->nullable()->after('barrio_vereda_sector')
                ->constrained('sectores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sector_id');
        });
    }
};
