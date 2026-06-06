<?php

namespace App\Http\Controllers\CasosUso\CU02AdministrarUsuario;

use App\Actions\CasosUso\CU02AdministrarUsuario\ActualizarUsuarioAction;
use App\Actions\CasosUso\CU02AdministrarUsuario\CambiarEstadoUsuarioAction;
use App\Actions\CasosUso\CU02AdministrarUsuario\CrearUsuarioAction;
use App\Actions\CasosUso\CU02AdministrarUsuario\RestablecerContrasenaUsuarioAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU02AdministrarUsuario\ActualizarContrasenaUsuarioRequest;
use App\Http\Requests\CasosUso\CU02AdministrarUsuario\ActualizarEstadoUsuarioRequest;
use App\Http\Requests\CasosUso\CU02AdministrarUsuario\ActualizarUsuarioRequest;
use App\Http\Requests\CasosUso\CU02AdministrarUsuario\GuardarUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ControladorUsuario extends Controller
{
    public function index(Request $request): Response
    {
        $buscar = trim((string) $request->string('buscar'));
        $buscarNormalizado = Str::lower($buscar);

        $usuarios = Usuario::query()
            ->select(['id_usuario', 'nombre', 'ci', 'correo', 'estado', 'rol', 'created_at', 'updated_at'])
            ->when($buscar !== '', function ($query) use ($buscarNormalizado): void {
                $query->where(function ($query) use ($buscarNormalizado): void {
                    $query
                        ->whereRaw('LOWER(nombre) LIKE ?', ["%{$buscarNormalizado}%"])
                        ->orWhereRaw('LOWER(correo) LIKE ?', ["%{$buscarNormalizado}%"])
                        ->orWhereRaw('LOWER(COALESCE(ci, \'\')) LIKE ?', ["%{$buscarNormalizado}%"]);
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Usuario $usuario): array => $this->presentarUsuario($usuario));

        return Inertia::render('casos-uso/cu02-administrar-usuario/index', [
            'usuarios' => $usuarios,
            'filtros' => [
                'buscar' => $buscar,
            ],
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
            'contrasenaInicial' => $request->session()->get('contrasena_inicial'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('casos-uso/cu02-administrar-usuario/create', [
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(GuardarUsuarioRequest $request, CrearUsuarioAction $crearUsuario): RedirectResponse
    {
        $resultado = $crearUsuario->execute($request->datosUsuario());
        $status = $resultado['credenciales_enviadas']
            ? 'Usuario registrado correctamente. Credenciales enviadas al correo.'
            : 'Usuario registrado correctamente.';

        return to_route('cu02.usuarios.index')
            ->with('status', $status)
            ->with(
                'contrasena_inicial',
                $resultado['credenciales_enviadas'] && $resultado['contrasena_generada']
                    ? $resultado['contrasena_inicial']
                    : null,
            );
    }

    public function edit(Request $request, Usuario $usuario): Response
    {
        return Inertia::render('casos-uso/cu02-administrar-usuario/edit', [
            'usuarioGestionado' => $this->presentarUsuario($usuario),
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(
        ActualizarUsuarioRequest $request,
        Usuario $usuario,
        ActualizarUsuarioAction $actualizarUsuario
    ): RedirectResponse {
        /** @var Usuario $actor */
        $actor = $request->user();

        $resultado = $actualizarUsuario->execute($usuario, $request->datosUsuario(), $actor);
        $status = $resultado['credenciales_enviadas']
            ? 'Usuario actualizado correctamente. Credenciales enviadas al correo.'
            : 'Usuario actualizado correctamente.';

        return back()
            ->with('status', $status)
            ->with('contrasena_inicial', $resultado['contrasena_inicial']);
    }

    public function updatePassword(
        ActualizarContrasenaUsuarioRequest $request,
        Usuario $usuario,
        RestablecerContrasenaUsuarioAction $restablecerContrasena
    ): RedirectResponse {
        $restablecerContrasena->execute($usuario, $request->nuevaContrasena());

        return back()->with('status', 'Contrasena restablecida correctamente.');
    }

    public function updateStatus(
        ActualizarEstadoUsuarioRequest $request,
        Usuario $usuario,
        CambiarEstadoUsuarioAction $cambiarEstado
    ): RedirectResponse {
        /** @var Usuario $actor */
        $actor = $request->user();

        $resultado = $cambiarEstado->execute($usuario, $request->estado(), $actor);
        $status = $resultado['credenciales_enviadas']
            ? 'Estado de cuenta actualizado. Credenciales enviadas al correo.'
            : 'Estado de cuenta actualizado.';

        return back()
            ->with('status', $status)
            ->with('contrasena_inicial', $resultado['contrasena_inicial']);
    }

    /**
     * @return array<string, Collection<int, array{value: string, label: string}>>
     */
    private function opcionesFormulario(): array
    {
        return [
            'roles' => $this->mapearOpciones(Usuario::rolesGestionables()),
            'estados' => $this->mapearOpciones(Usuario::estadosGestionables()),
        ];
    }

    /**
     * @param  array<string, string>  $opciones
     * @return Collection<int, array{value: string, label: string}>
     */
    private function mapearOpciones(array $opciones): Collection
    {
        return collect($opciones)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();
    }

    /**
     * @return array{id_usuario: int, nombre: string, ci: ?string, correo: string, estado: string, estado_label: string, rol: string, rol_label: string, created_at: ?string, updated_at: ?string}
     */
    private function presentarUsuario(Usuario $usuario): array
    {
        return [
            'id_usuario' => $usuario->id_usuario,
            'nombre' => $usuario->nombre,
            'ci' => $usuario->ci,
            'correo' => $usuario->correo,
            'estado' => $usuario->estado,
            'estado_label' => Usuario::estadosGestionables()[$usuario->estado] ?? $usuario->estado,
            'rol' => $usuario->rol,
            'rol_label' => Usuario::rolesGestionables()[$usuario->rol] ?? $usuario->rol,
            'created_at' => $usuario->created_at?->format('Y-m-d H:i'),
            'updated_at' => $usuario->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
