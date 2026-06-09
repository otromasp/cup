<?php

namespace Database\Factories;

use App\Models\CarreraCup;
use App\Models\GestionCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarreraCup>
 */
class CarreraCupFactory extends Factory
{
    protected $model = CarreraCup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gestion_cup_id' => GestionCup::factory(),
            'nombre_carrera' => fake()->randomElement(['Ingenieria de Sistemas', 'Ingenieria Informatica', 'Redes y Telecomunicaciones', 'Robotica']),
            'cupo_disponible' => fake()->numberBetween(40, 140),
            'cupo_ocupado' => 0,
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }
}
