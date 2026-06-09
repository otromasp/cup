<?php

namespace Database\Factories;

use App\Models\GestionCup;
use App\Models\RequisitoInscripcion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequisitoInscripcion>
 */
class RequisitoInscripcionFactory extends Factory
{
    protected $model = RequisitoInscripcion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gestion_cup_id' => GestionCup::factory(),
            'nombre_requisito' => fake()->randomElement(['Fotocopia de carnet de identidad', 'Libreta escolar', 'Comprobante de pago']),
            'obligatorio' => true,
            'tipo_requisito' => RequisitoInscripcion::TIPO_DECLARATIVO,
            'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }
}
