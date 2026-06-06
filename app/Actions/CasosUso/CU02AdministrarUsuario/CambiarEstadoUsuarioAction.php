<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class CambiarEstadoUsuarioAction
{
    public function __construct(
        private readonly EnviarCredencialesInicialesAction $enviarCredencialesIniciales,
    ) {}

    /**
     * @return array{usuario: Usuario, contrasena_inicial: ?string, credenciales_enviadas: bool}
     */
    public function execute(Usuario $usuario, string $estado, Usuario $actor): array
    {
        $this->validarCambioEstado($usuario, $estado, $actor);

        $estadoAnterior = $usuario->estado;
        $usuario->forceFill(['estado' => $estado])->save();
        $contrasenaInicial = null;
        $credencialesEnviadas = false;

        if (
            $estadoAnterior !== Usuario::ESTADO_ACTIVO
            && $estado === Usuario::ESTADO_ACTIVO
            && $usuario->credenciales_enviadas_en === null
        ) {
            $contrasenaInicial = $this->enviarCredencialesIniciales->execute(
                usuario: $usuario,
                actualizarContrasena: true,
            );
            $credencialesEnviadas = true;
        }

        return [
            'usuario' => $usuario,
            'contrasena_inicial' => $contrasenaInicial,
            'credenciales_enviadas' => $credencialesEnviadas,
        ];
    }

    private function validarCambioEstado(Usuario $usuario, string $estado, Usuario $actor): void
    {
        if ($usuario->is($actor) && $estado !== Usuario::ESTADO_ACTIVO) {
            throw ValidationException::withMessages([
                'estado' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        if ($usuario->rol !== Usuario::ROL_ADMINISTRADOR || $estado === Usuario::ESTADO_ACTIVO) {
            return;
        }

        $existeOtroAdministradorActivo = Usuario::query()
            ->whereKeyNot($usuario->getKey())
            ->where('rol', Usuario::ROL_ADMINISTRADOR)
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->exists();

        if (! $existeOtroAdministradorActivo) {
            throw ValidationException::withMessages([
                'estado' => 'Debe existir al menos un administrador activo.',
            ]);
        }
    }
}
