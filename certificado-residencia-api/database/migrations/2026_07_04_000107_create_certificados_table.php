<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes')->cascadeOnDelete();
            $table->string('consecutivo')->unique();          // CR-2026-00000001
            $table->string('codigo_verificacion')->unique();  // usado en consulta pública
            $table->string('hash_documento')->nullable();     // sha256 del PDF firmado
            $table->string('qr_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('firmado_por')->nullable()->constrained('users')->nullOnDelete(); // Alcalde
            $table->timestamp('fecha_expedicion')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('estado')->default('vigente');     // App\Enums\EstadoCertificado
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo_verificacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
