<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los usuarios ya no se crean con una contraseña elegida por el
        // admin: se genera una temporal, se envía por correo, y el usuario
        // debe cambiarla al primer login (ver AuthController::changePassword
        // y CredencialesTemporalesNotification).
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('password_expires_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'password_expires_at']);
        });
    }
};
