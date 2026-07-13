<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable a propósito: las solicitudes existentes (y las que sigan
        // llegando de VUR sin catálogo) conservan barrio_vereda_sector como
        // texto; sector_id es el nuevo vínculo estructurado que habilita el
        // scoping del Presidente JAC por sector.
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->foreignId('sector_id')->nullable()->after('barrio_vereda_sector')
                ->constrained('sectores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sector_id');
        });
    }
};
