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
        // ruta_pdf sigue siendo el borrador autogenerado (uso interno/auditoría).
        // ruta_pdf_firmado es el documento que el ciudadano imprime, firma a mano,
        // escanea y vuelve a subir — ese es el que realmente se envía a VUR como
        // pdf_solicitud, no el borrador (no hay firma electrónica en este canal).
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->string('ruta_pdf_firmado')->nullable()->after('ruta_pdf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->dropColumn('ruta_pdf_firmado');
        });
    }
};
