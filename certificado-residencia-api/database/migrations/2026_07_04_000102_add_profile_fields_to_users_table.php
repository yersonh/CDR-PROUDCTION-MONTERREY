<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo_documento')->nullable()->after('name');
            $table->string('numero_documento')->nullable()->unique()->after('tipo_documento');
            $table->string('celular')->nullable()->after('email');
            $table->foreignId('dependencia_id')->nullable()->after('celular')->constrained('dependencias')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('dependencia_id');
            $table->timestamp('last_login_at')->nullable()->after('activo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dependencia_id');
            $table->dropColumn([
                'tipo_documento', 'numero_documento', 'celular',
                'activo', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};
