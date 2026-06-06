<?php

namespace Database\Seeders;

use App\Models\Usuario;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario::factory(10)->create();

        $correoAdministrador = env('CUP_ADMIN_EMAIL') ?: env('MAIL_USERNAME', 'admin@cup.local');
        $contrasenaAdministrador = env('CUP_ADMIN_PASSWORD', 'Abel75087546$');

        // Usuario listo para probar el CU1 sin recuperar contrasena.
        Usuario::query()->updateOrCreate([
            'ci' => '13779759',
            'correo' => $correoAdministrador,
        ], [
            'nombre' => 'Abel Olivera Flores',
            'contrasena_hash' => $contrasenaAdministrador,
            'estado' => Usuario::ESTADO_ACTIVO,
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
    }
}
