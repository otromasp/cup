<?php

namespace Database\Factories;

use App\Models\GestionCup;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MateriaCup>
 */
class MateriaCupFactory extends Factory
{
    protected $model = MateriaCup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gestion_cup_id' => GestionCup::factory(),
            'nombre_materia' => fake()->randomElement(['Computacion', 'Matematica', 'Ingles', 'Fisica']),
            'ponderacion_nota1' => 30,
            'ponderacion_nota2' => 30,
            'ponderacion_nota3' => 40,
            'nota_minima' => 60,
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }
}
