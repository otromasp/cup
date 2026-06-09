<?php

namespace App\Http\Controllers\CasosUso\CU05GestionarDocente;

use App\Actions\CasosUso\CU05GestionarDocente\CambiarEstadoDocenteAction;
use App\Actions\CasosUso\CU05GestionarDocente\GuardarDocenteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU05GestionarDocente\CambiarEstadoDocenteRequest;
use App\Http\Requests\CasosUso\CU05GestionarDocente\GuardarDocenteRequest;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ControladorDocente extends Controller
{
    public function index(Request $request): Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $estado = trim((string) $request->query('estado', ''));

        $docentes = Docente::query()
            ->with(['materias.gestionCup:id_gestion,nombre_gestion,convocatoria', 'disponibilidades', 'usuario:id_usuario,nombre,correo,rol'])
            ->withCount(['materias', 'disponibilidades'])
            ->when($buscar !== '', fn (Builder $query) => $this->aplicarBusqueda($query, $buscar))
            ->when($estado !== '', fn (Builder $query) => $query->where('estado_contratacion', $estado))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Docente $docente): array => $this->presentarResumen($docente));

        return Inertia::render('casos-uso/cu05-gestionar-docente/index', [
            'docentes' => $docentes,
            'filtros' => [
                'buscar' => $buscar,
                'estado' => $estado,
            ],
            'opciones' => [
                'estadosContratacion' => $this->mapearOpciones(Docente::estadosContratacion()),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('casos-uso/cu05-gestionar-docente/create', [
            'docente' => $this->docentePredeterminado(),
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(GuardarDocenteRequest $request, GuardarDocenteAction $guardarDocente): RedirectResponse
    {
        $docente = $guardarDocente->execute($request->datosDocente());

        return to_route('cu05.docentes.edit', $docente)
            ->with('status', 'Docente registrado correctamente.');
    }

    public function edit(Request $request, Docente $docente): Response
    {
        $docente->load(['usuario', 'materias.gestionCup', 'disponibilidades']);

        return Inertia::render('casos-uso/cu05-gestionar-docente/edit', [
            'docente' => $this->presentarFormulario($docente),
            'opciones' => $this->opcionesFormulario($docente),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(
        GuardarDocenteRequest $request,
        Docente $docente,
        GuardarDocenteAction $guardarDocente
    ): RedirectResponse {
        $guardarDocente->execute($request->datosDocente(), $docente);

        return back()->with('status', 'Docente actualizado correctamente.');
    }

    public function updateStatus(
        CambiarEstadoDocenteRequest $request,
        Docente $docente,
        CambiarEstadoDocenteAction $cambiarEstadoDocente
    ): RedirectResponse {
        $cambiarEstadoDocente->execute($docente, (string) $request->validated('estado_contratacion'));

        return back()->with('status', 'Estado del docente actualizado correctamente.');
    }

    private function aplicarBusqueda(Builder $query, string $buscar): void
    {
        $query->where(function (Builder $query) use ($buscar): void {
            $query->where('nombres', 'ilike', "%{$buscar}%")
                ->orWhere('apellidos', 'ilike', "%{$buscar}%")
                ->orWhere('ci', 'ilike', "%{$buscar}%")
                ->orWhere('correo', 'ilike', "%{$buscar}%")
                ->orWhere('area_especialidad', 'ilike', "%{$buscar}%")
                ->orWhereHas('materias', fn (Builder $materia) => $materia->where('nombre_materia', 'ilike', "%{$buscar}%"));
        });
    }

    /**
     * @return array<string, Collection<int, array{value: string, label: string}>>
     */
    private function opcionesFormulario(?Docente $docente = null): array
    {
        return [
            'estadosContratacion' => $this->mapearOpciones(Docente::estadosContratacion()),
            'diasSemana' => $this->mapearOpciones(DisponibilidadDocente::diasSemana()),
            'turnos' => $this->mapearOpciones(DisponibilidadDocente::turnos()),
            'modalidades' => $this->mapearOpciones(DisponibilidadDocente::modalidades()),
            'materias' => MateriaCup::query()
                ->with('gestionCup:id_gestion,nombre_gestion,convocatoria')
                ->where('estado', Usuario::ESTADO_ACTIVO)
                ->orderBy('nombre_materia')
                ->get()
                ->map(fn (MateriaCup $materia): array => [
                    'value' => (string) $materia->id_materia_cup,
                    'label' => "{$materia->nombre_materia} / {$materia->gestionCup?->convocatoria}",
                ])
                ->values(),
            'usuariosDocente' => Usuario::query()
                ->where('rol', Usuario::ROL_DOCENTE)
                ->where(function (Builder $query) use ($docente): void {
                    $query->whereDoesntHave('docente');

                    if ($docente?->usuario_id) {
                        $query->orWhereKey($docente->usuario_id);
                    }
                })
                ->orderBy('nombre')
                ->get(['id_usuario', 'nombre', 'correo'])
                ->map(fn (Usuario $usuario): array => [
                    'value' => (string) $usuario->id_usuario,
                    'label' => "{$usuario->nombre} / {$usuario->correo}",
                ])
                ->values(),
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
     * @return array<string, mixed>
     */
    private function docentePredeterminado(): array
    {
        return [
            'id_docente' => null,
            'usuario_id' => '',
            'ci' => '',
            'nombres' => '',
            'apellidos' => '',
            'correo' => '',
            'telefono' => '',
            'profesion' => '',
            'area_especialidad' => '',
            'titulo_profesional_afin' => false,
            'tiene_maestria' => false,
            'tiene_diplomado_educacion_superior' => false,
            'maximo_grupos_asignables' => '4',
            'estado_contratacion' => Docente::ESTADO_OBSERVADO,
            'materias' => [],
            'disponibilidades' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarFormulario(Docente $docente): array
    {
        return [
            'id_docente' => $docente->id_docente,
            'usuario_id' => $docente->usuario_id ? (string) $docente->usuario_id : '',
            'ci' => $docente->ci,
            'nombres' => $docente->nombres,
            'apellidos' => $docente->apellidos,
            'correo' => $docente->correo,
            'telefono' => $docente->telefono ?? '',
            'profesion' => $docente->profesion,
            'area_especialidad' => $docente->area_especialidad,
            'titulo_profesional_afin' => (bool) $docente->titulo_profesional_afin,
            'tiene_maestria' => (bool) $docente->tiene_maestria,
            'tiene_diplomado_educacion_superior' => (bool) $docente->tiene_diplomado_educacion_superior,
            'maximo_grupos_asignables' => (string) $docente->maximo_grupos_asignables,
            'estado_contratacion' => $docente->estado_contratacion,
            'materias' => $docente->materias->pluck('id_materia_cup')->map(fn (int $id): string => (string) $id)->values()->all(),
            'disponibilidades' => $docente->disponibilidades
                ->map(fn (DisponibilidadDocente $disponibilidad): array => [
                    'id_disponibilidad_docente' => $disponibilidad->id_disponibilidad_docente,
                    'dia_semana' => $disponibilidad->dia_semana,
                    'turno' => $disponibilidad->turno,
                    'hora_inicio' => $this->hora($disponibilidad->hora_inicio),
                    'hora_fin' => $this->hora($disponibilidad->hora_fin),
                    'modalidad' => $disponibilidad->modalidad,
                    'observacion' => $disponibilidad->observacion ?? '',
                ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarResumen(Docente $docente): array
    {
        return [
            'id_docente' => $docente->id_docente,
            'nombre_completo' => $docente->nombreCompleto(),
            'ci' => $docente->ci,
            'correo' => $docente->correo,
            'telefono' => $docente->telefono,
            'profesion' => $docente->profesion,
            'area_especialidad' => $docente->area_especialidad,
            'estado_contratacion' => $docente->estado_contratacion,
            'estado_contratacion_label' => Docente::estadosContratacion()[$docente->estado_contratacion] ?? $docente->estado_contratacion,
            'maximo_grupos_asignables' => $docente->maximo_grupos_asignables,
            'materias_count' => $docente->materias_count,
            'disponibilidades_count' => $docente->disponibilidades_count,
            'materias' => $docente->materias->map(fn (MateriaCup $materia): string => $materia->nombre_materia)->values(),
            'modalidades' => $docente->disponibilidades->pluck('modalidad')->unique()->values(),
            'usuario' => $docente->usuario?->only(['id_usuario', 'nombre', 'correo']),
        ];
    }

    private function hora(mixed $valor): string
    {
        return substr((string) $valor, 0, 5);
    }
}
