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
        Schema::create('postulantes', function (Blueprint $table) {
            $table->id('id_postulante');
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('ci', 30)->unique();
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->string('correo', 160)->unique();
            $table->string('telefono', 40)->nullable();
            $table->string('colegio_procedencia', 160)->nullable();
            $table->unsignedSmallInteger('anio_bachillerato')->nullable();
            $table->boolean('es_extranjero')->default(false);
            $table->string('estado', 30)->default('registrado')->index();
            $table->timestamps();
        });

        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('id_inscripcion');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->restrictOnDelete();
            $table->foreignId('postulante_id')->constrained('postulantes', 'id_postulante')->restrictOnDelete();
            $table->foreignId('carrera_primera_id')->constrained('carreras_cup', 'id_carrera_cup')->restrictOnDelete();
            $table->foreignId('carrera_segunda_id')->nullable()->constrained('carreras_cup', 'id_carrera_cup')->nullOnDelete();
            $table->string('codigo_inscripcion', 40)->unique();
            $table->string('estado', 30)->default('pendiente_pago')->index();
            $table->timestamp('fecha_inscripcion')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'postulante_id']);
        });

        Schema::create('pagos_inscripcion', function (Blueprint $table) {
            $table->id('id_pago_inscripcion');
            $table->foreignId('inscripcion_id')->unique()->constrained('inscripciones', 'id_inscripcion')->cascadeOnDelete();
            $table->string('proveedor', 30)->default('stripe');
            $table->unsignedInteger('monto_centavos');
            $table->string('moneda', 3);
            $table->string('estado', 30)->default('pendiente')->index();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('codigo_comprobante')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
        });

        Schema::create('inscripcion_requisitos', function (Blueprint $table) {
            $table->id('id_inscripcion_requisito');
            $table->foreignId('inscripcion_id')->constrained('inscripciones', 'id_inscripcion')->cascadeOnDelete();
            $table->foreignId('requisito_id')->constrained('requisitos_inscripcion', 'id_requisito')->restrictOnDelete();
            $table->boolean('cumplido')->default(false);
            $table->string('origen', 30)->default('declarativo');
            $table->timestamp('cumplido_en')->nullable();
            $table->timestamps();

            $table->unique(['inscripcion_id', 'requisito_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripcion_requisitos');
        Schema::dropIfExists('pagos_inscripcion');
        Schema::dropIfExists('inscripciones');
        Schema::dropIfExists('postulantes');
    }
};
