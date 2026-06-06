<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Support\Str;

class RestablecerContrasenaUsuarioAction
{
    public function execute(Usuario $usuario, string $nuevaContrasena): Usuario
    {
        $usuario->forceFill([
            'contrasena_hash' => $nuevaContrasena,
            'remember_token' => Str::random(60),
        ])->save();

        return $usuario;
    }
}
