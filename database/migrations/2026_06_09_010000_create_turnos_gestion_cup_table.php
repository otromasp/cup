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
        Schema::create('turnos_gestion_cup', function (Blueprint $table) {
            $table->id('id_turno_gestion');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('turno', 20);
            $table->unsignedTinyInteger('orden');
            $table->unsignedInteger('capacidad_maxima');
            $table->string('modalidad', 20)->default('presencial');
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'turno']);
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->foreignId('turno_gestion_cup_id')
                ->nullable()
                ->after('carrera_segunda_id')
                ->constrained('turnos_gestion_cup', 'id_turno_gestion')
                ->nullOnDelete();

            $table->index(['gestion_cup_id', 'turno_gestion_cup_id', 'estado'], 'inscripciones_gestion_turno_estado_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropIndex('inscripciones_gestion_turno_estado_idx');
            $table->dropConstrainedForeignId('turno_gestion_cup_id');
        });

        Schema::dropIfExists('turnos_gestion_cup');
    }
};
