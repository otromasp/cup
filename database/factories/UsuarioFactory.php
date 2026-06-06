<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'correo_verificado_en' => now(),
            'contrasena_hash' => static::$password ??= Hash::make('password'),
            'estado' => Usuario::ESTADO_ACTIVO,
            'rol' => Usuario::ROL_POSTULANTE,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => 'inactivo',
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'correo_verificado_en' => null,
        ]);
    }
}
