<?php

namespace App\Actions\CasosUso\CU01GestionarAcceso;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IniciarSesionAction
{
    public function execute(string $correo, string $contrasena, bool $recordar): bool
    {
        return Auth::attempt([
            'correo' => Str::lower($correo),
            'password' => $contrasena,
            'estado' => Usuario::ESTADO_ACTIVO,
        ], $recordar);
    }
}
