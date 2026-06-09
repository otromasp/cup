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
        Schema::create('docentes', function (Blueprint $table) {
            $table->id('id_docente');
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios', 'id_usuario')->nullOnDelete();
            $table->string('ci', 30)->unique();
            $table->string('nombres', 120);
            $table->string('apellidos', 120);
            $table->string('correo', 160)->unique();
            $table->string('telefono', 40)->nullable();
            $table->string('profesion', 120);
            $table->string('area_especialidad', 120);
            $table->boolean('titulo_profesional_afin')->default(false);
            $table->boolean('tiene_maestria')->default(false);
            $table->boolean('tiene_diplomado_educacion_superior')->default(false);
            $table->unsignedTinyInteger('maximo_grupos_asignables')->default(4);
            $table->string('estado_contratacion', 30)->default('observado')->index();
            $table->timestamps();
        });

        Schema::create('docente_materia_cup', function (Blueprint $table) {
            $table->foreignId('docente_id')->constrained('docentes', 'id_docente')->cascadeOnDelete();
            $table->foreignId('materia_cup_id')->constrained('materias_cup', 'id_materia_cup')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['docente_id', 'materia_cup_id']);
        });

        Schema::create('disponibilidades_docente', function (Blueprint $table) {
            $table->id('id_disponibilidad_docente');
            $table->foreignId('docente_id')->constrained('docentes', 'id_docente')->cascadeOnDelete();
            $table->string('dia_semana', 20);
            $table->string('turno', 20);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('modalidad', 20);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->index(['docente_id', 'dia_semana', 'turno']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disponibilidades_docente');
        Schema::dropIfExists('docente_materia_cup');
        Schema::dropIfExists('docentes');
    }
};
