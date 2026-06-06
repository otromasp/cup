<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;

class CrearUsuarioAction
{
    public function __construct(
        private readonly GenerarContrasenaInicialAction $generarContrasenaInicial,
        private readonly EnviarCredencialesInicialesAction $enviarCredencialesIniciales,
    ) {}

    /**
     * @param  array{nombre: string, ci: ?string, correo: string, rol: string, estado: string, contrasena_hash: ?string}  $datos
     * @return array{usuario: Usuario, contrasena_inicial: string, contrasena_generada: bool, credenciales_enviadas: bool}
     */
    public function execute(array $datos): array
    {
        $contrasenaInicial = $datos['contrasena_hash'] ?: $this->generarContrasenaInicial->execute();
        $contrasenaGenerada = $datos['contrasena_hash'] === null;

        $datos['contrasena_hash'] = $contrasenaInicial;
        $usuario = Usuario::query()->create($datos);
        $credencialesEnviadas = false;

        if ($usuario->estado === Usuario::ESTADO_ACTIVO) {
            $this->enviarCredencialesIniciales->execute($usuario, $contrasenaInicial);
            $credencialesEnviadas = true;
        }

        return [
            'usuario' => $usuario,
            'contrasena_inicial' => $contrasenaInicial,
            'contrasena_generada' => $contrasenaGenerada,
            'credenciales_enviadas' => $credencialesEnviadas,
        ];
    }
}
