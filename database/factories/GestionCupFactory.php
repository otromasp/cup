<?php

namespace Database\Factories;

use App\Models\GestionCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GestionCup>
 */
class GestionCupFactory extends Factory
{
    protected $model = GestionCup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_responsable_id' => Usuario::factory()->coordinador(),
            'nombre_gestion' => 'CUP '.fake()->unique()->year(),
            'convocatoria' => 'Convocatoria '.fake()->unique()->numberBetween(1, 9),
            'fecha_inicio' => now()->addWeek()->toDateString(),
            'fecha_fin' => now()->addMonths(3)->toDateString(),
            'nota_minima_aprobacion' => 60,
            'costo_inscripcion' => 1000,
            'moneda_inscripcion' => GestionCup::MONEDA_BOB,
            'estado_configuracion' => GestionCup::ESTADO_EN_CONFIGURACION,
        ];
    }
}
