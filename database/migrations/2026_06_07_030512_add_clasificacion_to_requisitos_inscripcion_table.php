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
        Schema::table('requisitos_inscripcion', function (Blueprint $table) {
            $table->string('tipo_requisito', 30)->default('declarativo')->after('obligatorio')->index();
            $table->string('aplica_a', 30)->default('todos')->after('tipo_requisito')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitos_inscripcion', function (Blueprint $table) {
            $table->dropColumn(['tipo_requisito', 'aplica_a']);
        });
    }
};
