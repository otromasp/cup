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
        Schema::create('etapas_gestion_cup', function (Blueprint $table) {
            $table->id('id_etapa_gestion');
            $table->foreignId('gestion_cup_id')->constrained('gestiones_cup', 'id_gestion')->cascadeOnDelete();
            $table->string('nombre_etapa', 120);
            $table->unsignedSmallInteger('orden');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado_etapa', 20)->default('programada')->index();
            $table->timestamps();

            $table->unique(['gestion_cup_id', 'nombre_etapa']);
            $table->unique(['gestion_cup_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapas_gestion_cup');
    }
};
