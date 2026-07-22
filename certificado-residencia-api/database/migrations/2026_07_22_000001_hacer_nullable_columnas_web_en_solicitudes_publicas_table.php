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
        // Estas columnas eran NOT NULL porque hasta ahora toda fila nacía del
        // formulario público (que sí las exige todas). Con la radicación
        // manual en VUR (correo/ventanilla presencial, ver
        // SolicitudPublicaController::registrarDesdeVur) el operador de VUR
        // no captura tipo_certificado/medio_acreditacion/barrio_vereda_sector
        // (son conceptos propios del formulario público de CDR), y a veces
        // tampoco correo/celular/dirección completos — y no hay PDF generado
        // por CDR (VUR ya tiene el suyo), así que ruta_pdf también debe poder
        // quedar vacía para esas filas.
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->string('numero_identificacion')->nullable()->change();
            $table->string('direccion')->nullable()->change();
            $table->string('correo')->nullable()->change();
            $table->string('celular')->nullable()->change();
            $table->string('barrio_vereda_sector')->nullable()->change();
            $table->string('tipo_certificado')->nullable()->change();
            $table->string('medio_acreditacion')->nullable()->change();
            $table->string('ruta_pdf')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_publicas', function (Blueprint $table) {
            $table->string('numero_identificacion')->nullable(false)->change();
            $table->string('direccion')->nullable(false)->change();
            $table->string('correo')->nullable(false)->change();
            $table->string('celular')->nullable(false)->change();
            $table->string('barrio_vereda_sector')->nullable(false)->change();
            $table->string('tipo_certificado')->nullable(false)->change();
            $table->string('medio_acreditacion')->nullable(false)->change();
            $table->string('ruta_pdf')->nullable(false)->change();
        });
    }
};
