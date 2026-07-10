<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bandeja de entrada de solicitudes de Carta de Residencia enviadas
        // directamente (peer-to-peer) desde VUR. Separada de "solicitudes":
        // no se crea una Solicitud automáticamente, la secretaría la crea
        // manualmente desde el wizard existente precargando estos datos.
        Schema::create('recibidos_vur', function (Blueprint $table) {
            $table->id();
            $table->string('radicado_vur')->unique();
            $table->string('nombre_completo');
            $table->string('tipo_documento')->nullable();
            $table->string('numero_identificacion')->nullable();
            $table->string('correo')->nullable();
            $table->string('celular')->nullable();
            $table->string('nombre_original_pdf');
            $table->string('ruta_pdf');
            $table->string('estado')->default('pendiente'); // pendiente | procesado
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes')->nullOnDelete();
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibidos_vur');
    }
};
