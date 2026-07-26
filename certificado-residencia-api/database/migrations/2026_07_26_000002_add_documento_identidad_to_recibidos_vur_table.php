<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            // Cédula de ciudadanía reenviada por VUR (anexo obligatorio de
            // Carta de Residencia allá) — permite que
            // RecibidoVurService::procesarAutomaticamente() la adjunte al
            // expediente como "documento_identidad", igual que cuando la
            // solicitud llega por el formulario público de CDR.
            $table->string('ruta_documento_identidad')->nullable()->after('nombre_original_soporte');
            $table->string('nombre_original_documento_identidad')->nullable()->after('ruta_documento_identidad');
        });
    }

    public function down(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->dropColumn(['ruta_documento_identidad', 'nombre_original_documento_identidad']);
        });
    }
};
