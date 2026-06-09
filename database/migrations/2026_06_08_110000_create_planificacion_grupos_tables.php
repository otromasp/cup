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
        Schema::create('grupos_cup', function (Blueprint $table) {
            $table->id('id_grupo_cup');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre_grupo', 80);
            $table->unsignedSmallInteger('numero_grupo');
            $table->unsignedTinyInteger('capacidad_maxima')->default(70);
            $table->string('turno', 20);
            $table->string('estado', 30)->default('en_planificacion')->index();
            $table->timestamp('publicado_en')->nullable();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'numero_grupo']);
            $table->unique(['gestion_cup_id', 'nombre_grupo']);
        });

        Schema::create('inscripcion_grupo', function (Blueprint $table) {
            $table->id('id_inscripcion_grupo');
            $table->foreignId('grupo_cup_id')->constrained('grupos_cup', 'id_grupo_cup')->cascadeOnDelete();
            $table->foreignId('inscripcion_id')->unique()->constrained('inscripciones', 'id_inscripcion')->cascadeOnDelete();
            $table->string('estado', 30)->default('asignada')->index();
            $table->timestamp('asignado_en')->nullable();
            $table->timestamps();

            $table->index(['grupo_cup_id', 'estado']);
        });

        Schema::create('horarios_grupo_cup', function (Blueprint $table) {
            $table->id('id_horario_grupo_cup');
            $table->foreignId('grupo_cup_id')->constrained('grupos_cup', 'id_grupo_cup')->cascadeOnDelete();
            $table->string('dia_semana', 20);
            $table->string('turno', 20);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('modalidad', 20);
            $table->string('aula', 80)->nullable();
            $table->string('enlace_clase', 255)->nullable();
            $table->timestamps();

            $table->index(['grupo_cup_id', 'dia_semana', 'turno']);
        });

        Schema::create('asignaciones_grupo', function (Blueprint $table) {
            $table->id('id_asignacion_grupo');
            $table->foreignId('grupo_cup_id')->constrained('grupos_cup', 'id_grupo_cup')->cascadeOnDelete();
            $table->foreignId('materia_cup_id')->constrained('materias_cup', 'id_materia_cup')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('docentes', 'id_docente')->restrictOnDelete();
            $table->foreignId('horario_grupo_cup_id')->unique()->constrained('horarios_grupo_cup', 'id_horario_grupo_cup')->cascadeOnDelete();
            $table->string('estado', 30)->default('asignada')->index();
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->unique(['grupo_cup_id', 'materia_cup_id']);
            $table->index(['docente_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_grupo');
        Schema::dropIfExists('horarios_grupo_cup');
        Schema::dropIfExists('inscripcion_grupo');
        Schema::dropIfExists('grupos_cup');
    }
};
