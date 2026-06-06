<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use App\Notifications\CasosUso\CU02AdministrarUsuario\EnviarCredencialesIniciales;
use Illuminate\Support\Str;

class EnviarCredencialesInicialesAction
{
    public function __construct(
        private readonly GenerarContrasenaInicialAction $generarContrasenaInicial,
    ) {}

    public function execute(Usuario $usuario, ?string $contrasenaInicial = null, bool $actualizarContrasena = false): string
    {
        $contrasenaInicial ??= $this->generarContrasenaInicial->execute();

        if ($actualizarContrasena) {
            $usuario->forceFill([
                'contrasena_hash' => $contrasenaInicial,
                'remember_token' => Str::random(60),
            ])->save();
        }

        $usuario->notify(new EnviarCredencialesIniciales($usuario->correo, $contrasenaInicial));

        $usuario->forceFill(['credenciales_enviadas_en' => now()])->save();

        return $contrasenaInicial;
    }
}
