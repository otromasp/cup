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
        Schema::table('gestiones_cup', function (Blueprint $table) {
            $table->decimal('costo_inscripcion', 10, 2)->default(1000)->after('nota_minima_aprobacion');
            $table->string('moneda_inscripcion', 3)->default('bob')->after('costo_inscripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestiones_cup', function (Blueprint $table) {
            $table->dropColumn(['costo_inscripcion', 'moneda_inscripcion']);
        });
    }
};
