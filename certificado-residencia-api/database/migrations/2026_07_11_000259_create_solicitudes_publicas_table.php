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
        // Puerta de entrada pública (sin login): un ciudadano diligencia el
        // formulario, se genera un PDF estándar de solicitud y se envía a
        // VUR para que allá se radique. VUR luego reenvía la solicitud ya
        // radicada de vuelta a CDR vía "recibidos_vur" para que secretaría
        // la formalice con el wizard interno. Esta tabla es solo trazabilidad
        // del envío saliente hacia VUR, no crea una Solicitud/Expediente aquí.
        Schema::create('solicitudes_publicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('tipo_documento')->nullable();
            $table->string('numero_identificacion');
            $table->string('direccion');
            $table->string('correo');
            $table->string('celular');
            $table->string('barrio_vereda_sector');
            $table->text('motivo')->nullable();
            $table->string('tipo_certificado');
            $table->string('medio_acreditacion');
            $table->text('justificacion_especial')->nullable();
            $table->string('ruta_soporte')->nullable();
            $table->string('ruta_pdf');
            $table->string('estado')->default('pendiente'); // pendiente | enviado | error
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->string('radicado_vur')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_publicas');
    }
};
