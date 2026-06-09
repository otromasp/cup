<?php

namespace Database\Factories;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EtapaGestionCup>
 */
class EtapaGestionCupFactory extends Factory
{
    protected $model = EtapaGestionCup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'gestion_cup_id' => GestionCup::factory(),
            'nombre_etapa' => 'Etapa '.fake()->unique()->numberBetween(1, 999),
            'orden' => fake()->unique()->numberBetween(1, 999),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => (clone $inicio)->modify('+7 days')->format('Y-m-d'),
            'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
        ];
    }

    public function activa(): static
    {
        return $this->state(fn (): array => [
            'estado_etapa' => EtapaGestionCup::ESTADO_ACTIVA,
        ]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (): array => [
            'estado_etapa' => EtapaGestionCup::ESTADO_CERRADA,
        ]);
    }
}
