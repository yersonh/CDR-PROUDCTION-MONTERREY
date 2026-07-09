<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expediente_documentos', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('es_certificado');
            $table->boolean('vigente')->default(true)->after('version');
            $table->foreignId('reemplaza_a')->nullable()->after('vigente')
                ->constrained('expediente_documentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expediente_documentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reemplaza_a');
            $table->dropColumn(['version', 'vigente']);
        });
    }
};
