<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de barrios/veredas del municipio — reemplaza el texto
        // libre que hasta ahora se escribía a mano en el wizard público y en
        // la validación JAC (ver PresidenteJac, que ata un presidente a un
        // sector).
        Schema::create('sectores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('tipo'); // barrio | vereda
            $table->string('zona'); // urbana | rural
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectores');
    }
};
