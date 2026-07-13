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
        // referencia_cdr es el id de SolicitudPublica que CDR le manda a VUR
        // al pedir la radicación (ver EnviarSolicitudPublicaAVur) — si VUR lo
        // reenvía aquí al confirmar el radicado, es un identificador mucho
        // más confiable para deduplicar que radicado_vur: un dato de prueba
        // o un reset de numeración en VUR puede hacer que un radicado_vur
        // nuevo choque con uno viejo que ya teníamos, sin ser el mismo envío
        // real. Sigue siendo nullable porque VUR también puede originar un
        // recibido directamente (sin pasar por el formulario público de
        // CDR) — para ese caso se sigue deduplicando por radicado_vur.
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->unsignedBigInteger('referencia_cdr')->nullable()->unique()->after('radicado_vur');
        });

        // radicado_vur deja de ser único a nivel de BD: la deduplicación
        // ahora la decide RecibidoVurService (por referencia_cdr cuando
        // viene, por radicado_vur si no) en vez de depender ciegamente del
        // constraint. Se conserva como índice normal para búsquedas.
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->dropUnique(['radicado_vur']);
            $table->index('radicado_vur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->dropIndex(['radicado_vur']);
            $table->unique('radicado_vur');
            $table->dropColumn('referencia_cdr');
        });
    }
};
