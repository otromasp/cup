<?php

namespace Database\Factories;

use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisponibilidadDocente>
 */
class DisponibilidadDocenteFactory extends Factory
{
    protected $model = DisponibilidadDocente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'docente_id' => Docente::factory(),
            'dia_semana' => fake()->randomElement(array_keys(DisponibilidadDocente::diasSemana())),
            'turno' => fake()->randomElement(array_keys(DisponibilidadDocente::turnos())),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'modalidad' => fake()->randomElement(array_keys(DisponibilidadDocente::modalidades())),
            'observacion' => null,
        ];
    }
}
