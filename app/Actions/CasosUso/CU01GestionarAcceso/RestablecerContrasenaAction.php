<?php

namespace App\Actions\CasosUso\CU01GestionarAcceso;

use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestablecerContrasenaAction
{
    public function execute(string $correo, string $tokenPlano, string $nuevaContrasena): bool
    {
        $usuario = Usuario::query()
            ->where('correo', Str::lower($correo))
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->first();

        if (! $usuario) {
            return false;
        }

        $token = $usuario->tokensRecuperacion()
            ->vigente()
            ->latest('id_token')
            ->get()
            ->first(fn ($token) => Hash::check($tokenPlano, $token->codigo_token));

        if (! $token) {
            return false;
        }

        DB::transaction(function () use ($usuario, $token, $nuevaContrasena): void {
            $usuario->forceFill([
                'contrasena_hash' => Hash::make($nuevaContrasena),
                'remember_token' => Str::random(60),
            ])->save();

            $token->marcarUsado();
        });

        event(new PasswordReset($usuario));

        return true;
    }
}
