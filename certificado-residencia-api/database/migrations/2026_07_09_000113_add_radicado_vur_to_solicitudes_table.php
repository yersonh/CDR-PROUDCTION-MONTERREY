<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trazabilidad rápida hacia el radicado de origen en VUR, sin
        // depender siempre del join con recibidos_vur.
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->string('radicado_vur')->nullable()->unique()->after('radicado');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn('radicado_vur');
        });
    }
};
