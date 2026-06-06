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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre');
            $table->string('ci')->unique()->nullable();
            $table->string('correo')->unique();
            $table->timestamp('correo_verificado_en')->nullable();
            $table->string('contrasena_hash');
            $table->string('estado', 20)->default('activo')->index();
            $table->string('rol', 30)->default('postulante')->index();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tokens_recuperacion_contrasena', function (Blueprint $table) {
            $table->id('id_token');
            $table->foreignId('usuario_id')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->string('codigo_token');
            $table->timestamp('fecha_expiracion')->index();
            $table->timestamp('usado_en')->nullable();
            $table->string('estado', 20)->default('vigente')->index();
            $table->timestamps();

            $table->index(['usuario_id', 'estado']);
        });

        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->string('id')->primary();
            // Laravel escribe user_id automaticamente en sesiones de base de datos.
            $table->foreignId('user_id')->nullable()->index()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_usuario');
        Schema::dropIfExists('tokens_recuperacion_contrasena');
        Schema::dropIfExists('usuarios');
    }
};
