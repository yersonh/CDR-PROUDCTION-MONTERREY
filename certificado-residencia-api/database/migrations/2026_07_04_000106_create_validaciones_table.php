<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->string('tipo'); // electoral, sisben, jac, especial
            $table->string('resultado')->nullable(); // App\Enums\ResultadoValidacion
            $table->text('observacion')->nullable();

            // Metadatos específicos (p. ej. JAC: codigo_verificacion, fecha_expedicion,
            // fecha_vencimiento, presidente, sector, qr)
            $table->json('meta')->nullable();

            $table->foreignId('documento_id')->nullable()->constrained('expediente_documentos')->nullOnDelete();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones');
    }
};
