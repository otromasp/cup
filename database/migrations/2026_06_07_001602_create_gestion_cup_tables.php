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
        Schema::create('gestiones_cup', function (Blueprint $table) {
            $table->id('id_gestion');
            $table->foreignId('usuario_responsable_id')->constrained('usuarios', 'id_usuario')->restrictOnDelete();
            $table->string('nombre_gestion', 120);
            $table->string('convocatoria', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('nota_minima_aprobacion', 5, 2)->default(60);
            $table->string('estado_configuracion', 30)->default('en_configuracion')->index();
            $table->timestamps();

            $table->unique(['nombre_gestion', 'convocatoria']);
        });

        Schema::create('carreras_cup', function (Blueprint $table) {
            $table->id('id_carrera_cup');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre_carrera', 120);
            $table->unsignedInteger('cupo_disponible');
            $table->unsignedInteger('cupo_ocupado')->default(0);
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'nombre_carrera']);
        });

        Schema::create('materias_cup', function (Blueprint $table) {
            $table->id('id_materia_cup');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre_materia', 120);
            $table->decimal('ponderacion_nota1', 5, 2);
            $table->decimal('ponderacion_nota2', 5, 2);
            $table->decimal('ponderacion_nota3', 5, 2);
            $table->decimal('nota_minima', 5, 2);
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'nombre_materia']);
        });

        Schema::create('requisitos_inscripcion', function (Blueprint $table) {
            $table->id('id_requisito');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre_requisito', 160);
            $table->boolean('obligatorio')->default(true);
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'nombre_requisito']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitos_inscripcion');
        Schema::dropIfExists('materias_cup');
        Schema::dropIfExists('carreras_cup');
        Schema::dropIfExists('gestiones_cup');
    }
};
