<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Presidente de Junta de Acción Comunal vigente (o histórico) de
        // cada sector. Un reemplazo no borra el registro anterior: se marca
        // "reemplazado" y se crea uno nuevo — conserva la trazabilidad de
        // quién certificó qué en cada periodo (ver PresidenteJacService).
        Schema::create('presidentes_jac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->restrictOnDelete();
            $table->string('nombre_completo');
            $table->string('tipo_documento');
            $table->string('numero_identificacion');
            $table->string('direccion');
            $table->string('celular');
            $table->string('correo')->nullable();
            $table->date('fecha_inicio_periodo');
            $table->date('fecha_fin_periodo')->nullable();
            $table->string('estado')->default('activo'); // activo | reemplazado
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sector_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presidentes_jac');
    }
};
