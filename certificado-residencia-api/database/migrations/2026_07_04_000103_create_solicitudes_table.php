<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('radicado')->unique();

            // Ciudadano titular (puede ser un usuario registrado o radicado por recepción)
            $table->foreignId('ciudadano_id')->nullable()->constrained('users')->nullOnDelete();

            // Datos del trámite
            $table->string('tipo_certificado');       // App\Enums\TipoCertificado
            $table->string('medio_acreditacion');     // App\Enums\MedioAcreditacion

            // Datos del ciudadano (snapshot al momento de radicar)
            $table->string('nombre_completo');
            $table->string('tipo_documento')->nullable();
            $table->string('numero_identificacion');
            $table->string('direccion');
            $table->string('correo');
            $table->string('celular');
            $table->string('barrio_vereda_sector');
            $table->text('motivo')->nullable();

            // Estado y flujo
            $table->string('estado')->default('radicada'); // App\Enums\EstadoSolicitud
            $table->text('justificacion_especial')->nullable();
            $table->foreignId('dependencia_id')->nullable()->constrained('dependencias')->nullOnDelete();

            // Términos administrativos
            $table->timestamp('fecha_radicacion');
            $table->timestamp('fecha_limite_sla')->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('numero_identificacion');
            $table->index('barrio_vereda_sector');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
