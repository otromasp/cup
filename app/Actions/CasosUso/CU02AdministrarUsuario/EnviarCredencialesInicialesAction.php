<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use App\Notifications\CasosUso\CU02AdministrarUsuario\EnviarCredencialesIniciales;
use Illuminate\Support\Str;
use Throwable;

class EnviarCredencialesInicialesAction
{
    public function __construct(
        private readonly GenerarContrasenaInicialAction $generarContrasenaInicial,
    ) {}

    /**
     * @return array{contrasena_inicial: string, enviado: bool, error: ?string}
     */
    public function execute(Usuario $usuario, ?string $contrasenaInicial = null, bool $actualizarContrasena = false): array
    {
        $contrasenaInicial ??= $this->generarContrasenaInicial->execute();

        if ($actualizarContrasena) {
            $usuario->forceFill([
                'contrasena_hash' => $contrasenaInicial,
                'remember_token' => Str::random(60),
            ])->save();
        }

        try {
            $usuario->notify(new EnviarCredencialesIniciales($usuario->correo, $contrasenaInicial));
        } catch (Throwable $exception) {
            report($exception);

            return [
                'contrasena_inicial' => $contrasenaInicial,
                'enviado' => false,
                'error' => 'No se pudo enviar el correo de credenciales. Verifica la configuracion SMTP.',
            ];
        }

        $usuario->forceFill(['credenciales_enviadas_en' => now()])->save();

        return [
            'contrasena_inicial' => $contrasenaInicial,
            'enviado' => true,
            'error' => null,
        ];
    }
}
