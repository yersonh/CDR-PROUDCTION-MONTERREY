<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            // Mismo concepto que SolicitudPublica::medio_acreditacion — VUR
            // ya lo captura al radicar directamente una Solicitud Carta de
            // Residencia (electoral/sisben/jac) y lo reenvía aquí para que
            // RecibidoVurService::procesarAutomaticamente() pueda enrutar el
            // trámite al mismo flujo de validación que si hubiera venido del
            // formulario público.
            $table->string('medio_acreditacion')->nullable()->after('motivo');
            // Soporte opcional del medio de acreditación — hoy solo aplica a
            // Certificado Electoral (SISBEN/JAC no se adjuntan desde VUR,
            // igual que en el formulario público). Si viene vacío para
            // electoral, procesarAutomaticamente() no dispara la validación
            // IA y en cambio notifica a Secretaría para pedir subsanación.
            $table->string('ruta_soporte')->nullable()->after('medio_acreditacion');
            $table->string('nombre_original_soporte')->nullable()->after('ruta_soporte');
        });
    }

    public function down(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->dropColumn(['medio_acreditacion', 'ruta_soporte', 'nombre_original_soporte']);
        });
    }
};
