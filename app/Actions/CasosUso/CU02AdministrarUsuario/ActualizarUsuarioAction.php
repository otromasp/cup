<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class ActualizarUsuarioAction
{
    public function __construct(
        private readonly EnviarCredencialesInicialesAction $enviarCredencialesIniciales,
    ) {}

    /**
     * @param  array{nombre: string, ci: ?string, correo: string, rol: string, estado: string}  $datos
     * @return array{usuario: Usuario, contrasena_inicial: ?string, credenciales_enviadas: bool}
     */
    public function execute(Usuario $usuario, array $datos, Usuario $actor): array
    {
        $this->validarProteccionAdministrativa($usuario, $datos, $actor);

        $estadoAnterior = $usuario->estado;
        $usuario->forceFill($datos)->save();
        $contrasenaInicial = null;
        $credencialesEnviadas = false;

        if (
            $estadoAnterior !== Usuario::ESTADO_ACTIVO
            && $usuario->estado === Usuario::ESTADO_ACTIVO
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

    /**
     * @param  array{rol: string, estado: string}  $datos
     */
    private function validarProteccionAdministrativa(Usuario $usuario, array $datos, Usuario $actor): void
    {
        if ($usuario->is($actor) && ($datos['rol'] !== Usuario::ROL_ADMINISTRADOR || $datos['estado'] !== Usuario::ESTADO_ACTIVO)) {
            throw ValidationException::withMessages([
                'rol' => 'No puedes quitarte el acceso administrativo a ti mismo.',
            ]);
        }

        $pierdeAccesoAdministrador = $usuario->rol === Usuario::ROL_ADMINISTRADOR
            && ($datos['rol'] !== Usuario::ROL_ADMINISTRADOR || $datos['estado'] !== Usuario::ESTADO_ACTIVO);

        if (! $pierdeAccesoAdministrador) {
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
