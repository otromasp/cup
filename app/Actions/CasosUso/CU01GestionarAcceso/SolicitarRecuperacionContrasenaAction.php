<?php

namespace App\Actions\CasosUso\CU01GestionarAcceso;

use App\Models\TokenRecuperacionContrasena;
use App\Models\Usuario;
use App\Notifications\CasosUso\CU01GestionarAcceso\EnviarTokenRecuperacionContrasena;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SolicitarRecuperacionContrasenaAction
{
    public function execute(string $correo): void
    {
        $usuario = Usuario::query()
            ->where('correo', Str::lower($correo))
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->first();

        // No se informa si el correo existe para evitar enumeracion de cuentas.
        if (! $usuario) {
            return;
        }

        $tokenPlano = Str::random(64);
        $minutosVigencia = (int) config('auth.passwords.users.expire', 60);

        DB::transaction(function () use ($usuario, $tokenPlano, $minutosVigencia): void {
            $usuario->tokensRecuperacion()
                ->where('estado', TokenRecuperacionContrasena::ESTADO_VIGENTE)
                ->update(['estado' => TokenRecuperacionContrasena::ESTADO_REEMPLAZADO]);

            $usuario->tokensRecuperacion()->create([
                'codigo_token' => Hash::make($tokenPlano),
                'fecha_expiracion' => now()->addMinutes($minutosVigencia),
                'estado' => TokenRecuperacionContrasena::ESTADO_VIGENTE,
            ]);
        });

        $usuario->notify(new EnviarTokenRecuperacionContrasena($tokenPlano, $usuario->correo));
    }
}
