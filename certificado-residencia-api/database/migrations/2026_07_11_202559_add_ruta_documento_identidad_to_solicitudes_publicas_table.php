<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->string('ruta_documento_identidad')->nullable()->after('ruta_soporte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->dropColumn('ruta_documento_identidad');
        });
    }
};
