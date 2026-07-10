<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->string('direccion')->nullable()->after('celular');
            $table->text('motivo')->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('recibidos_vur', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'motivo']);
        });
    }
};
