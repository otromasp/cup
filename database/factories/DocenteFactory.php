<?php

namespace Database\Factories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ci' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'profesion' => fake()->randomElement(['Ingeniero de Sistemas', 'Ingeniero Informatico', 'Licenciado en Matematica']),
            'area_especialidad' => fake()->randomElement(['Computacion', 'Matematica', 'Ingles', 'Fisica']),
            'titulo_profesional_afin' => true,
            'tiene_maestria' => true,
            'tiene_diplomado_educacion_superior' => true,
            'maximo_grupos_asignables' => 4,
            'estado_contratacion' => Docente::ESTADO_OBSERVADO,
        ];
    }

    public function habilitado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado_contratacion' => Docente::ESTADO_HABILITADO,
            'titulo_profesional_afin' => true,
            'tiene_maestria' => true,
            'tiene_diplomado_educacion_superior' => true,
        ]);
    }
}
