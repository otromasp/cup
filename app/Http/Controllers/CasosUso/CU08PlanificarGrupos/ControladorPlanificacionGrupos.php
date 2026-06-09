<?php

namespace App\Http\Controllers\CasosUso\CU08PlanificarGrupos;

use App\Actions\CasosUso\CU08PlanificarGrupos\GenerarGruposCupAction;
use App\Actions\CasosUso\CU08PlanificarGrupos\GenerarHorariosGruposCupAction;
use App\Actions\CasosUso\CU08PlanificarGrupos\GuardarAsignacionGrupoAction;
use App\Actions\CasosUso\CU08PlanificarGrupos\PublicarPlanificacionGruposAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU08PlanificarGrupos\GenerarGruposRequest;
use App\Http\Requests\CasosUso\CU08PlanificarGrupos\GuardarAsignacionGrupoRequest;
use App\Models\AsignacionGrupo;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\Inscripcion;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ControladorPlanificacionGrupos extends Controller
{
    public function index(Request $request): Response
    {
        $gestionId = (int) $request->query('gestion_id', 0);
        $gestionCup = $this->gestionSeleccionada($gestionId);

        if ($gestionCup instanceof GestionCup) {
            $gestionCup->load([
                'materias' => fn ($query) => $query->where('estado', Usuario::ESTADO_ACTIVO)->orderBy('nombre_materia'),
                'grupos.inscripcionesAsignadas',
                'grupos.asignaciones.materiaCup',
                'grupos.asignaciones.docente',
                'grupos.asignaciones.horarioGrupoCup',
            ]);
        }

        return Inertia::render('casos-uso/cu08-planificar-grupos/index', [
            'gestion' => $gestionCup ? $this->presentarGestion($gestionCup) : null,
            'resumen' => $gestionCup ? $this->presentarResumen($gestionCup) : null,
            'grupos' => $gestionCup ? $this->presentarGrupos($gestionCup) : [],
            'filtros' => [
                'gestion_id' => $gestionCup ? (string) $gestionCup->id_gestion : '',
            ],
            'opciones' => [
                'gestiones' => $this->opcionesGestiones(),
                'materias' => $gestionCup ? $this->opcionesMaterias($gestionCup) : [],
                'docentes' => $gestionCup ? $this->opcionesDocentes($gestionCup) : [],
                'diasSemana' => $this->mapearOpciones(DisponibilidadDocente::diasSemana()),
                'turnos' => $this->mapearOpciones(DisponibilidadDocente::turnos()),
                'modalidades' => $this->mapearOpciones(DisponibilidadDocente::modalidades()),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function generate(GenerarGruposRequest $request, GenerarGruposCupAction $generarGrupos): RedirectResponse
    {
        $gestionCup = GestionCup::query()->findOrFail($request->gestionCupId());
        $resultado = $generarGrupos->execute($gestionCup);

        return to_route('cu08.planificacion-grupos.index', ['gestion_id' => $gestionCup->id_gestion])
            ->with('status', "Se generaron {$resultado['grupos_generados']} grupos y se asignaron {$resultado['inscripciones_asignadas']} inscripciones.");
    }

    public function storeAssignment(
        GuardarAsignacionGrupoRequest $request,
        GuardarAsignacionGrupoAction $guardarAsignacion
    ): RedirectResponse {
        $asignacion = $guardarAsignacion->execute($request->datosAsignacion());

        return to_route('cu08.planificacion-grupos.index', ['gestion_id' => $asignacion->grupoCup->gestion_cup_id])
            ->with('status', 'Asignacion academica guardada correctamente.');
    }

    public function generateSchedule(GenerarGruposRequest $request, GenerarHorariosGruposCupAction $generarHorarios): RedirectResponse
    {
        $gestionCup = GestionCup::query()->findOrFail($request->gestionCupId());
        $resultado = $generarHorarios->execute($gestionCup);
        $status = "Horarios generados: {$resultado['asignaciones_generadas']}.";

        if ($resultado['asignaciones_pendientes'] > 0) {
            $status .= " Pendientes por revisar: {$resultado['asignaciones_pendientes']}.";
        }

        if ($resultado['asignaciones_generadas'] === 0 && $resultado['asignaciones_pendientes'] === 0) {
            $status = 'La planificacion ya tenia todas las asignaciones academicas.';
        }

        return to_route('cu08.planificacion-grupos.index', ['gestion_id' => $gestionCup->id_gestion])
            ->with('status', $status);
    }

    public function publish(GenerarGruposRequest $request, PublicarPlanificacionGruposAction $publicarPlanificacion): RedirectResponse
    {
        $gestionCup = GestionCup::query()->findOrFail($request->gestionCupId());
        $resultado = $publicarPlanificacion->execute($gestionCup);
        $status = "Planificacion publicada: {$resultado['grupos_publicados']} grupos y {$resultado['usuarios_activados']} cuentas habilitadas.";

        if ($resultado['error_envio_credenciales']) {
            $status .= ' '.$resultado['error_envio_credenciales'];
        } elseif ($resultado['credenciales_enviadas'] > 0) {
            $status .= " Credenciales enviadas: {$resultado['credenciales_enviadas']}.";
        }

        return to_route('cu08.planificacion-grupos.index', ['gestion_id' => $gestionCup->id_gestion])
            ->with('status', $status);
    }

    private function gestionSeleccionada(int $gestionId): ?GestionCup
    {
        if ($gestionId > 0) {
            return GestionCup::query()->find($gestionId);
        }

        return GestionCup::query()
            ->where('estado_configuracion', GestionCup::ESTADO_CONFIGURADA)
            ->orderByDesc('id_gestion')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarGestion(GestionCup $gestionCup): array
    {
        return [
            'id_gestion' => $gestionCup->id_gestion,
            'nombre_gestion' => $gestionCup->nombre_gestion,
            'convocatoria' => $gestionCup->convocatoria,
            'fecha_inicio' => $gestionCup->fecha_inicio?->toDateString(),
            'fecha_fin' => $gestionCup->fecha_fin?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarResumen(GestionCup $gestionCup): array
    {
        $inscripcionesConfirmadas = Inscripcion::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
            ->count();
        $gruposNecesarios = $inscripcionesConfirmadas > 0 ? (int) ceil($inscripcionesConfirmadas / GrupoCup::CAPACIDAD_MAXIMA) : 0;
        $grupos = $gestionCup->grupos;
        $materiasActivas = $gestionCup->materias->count();
        $asignacionesEsperadas = $grupos->count() * $materiasActivas;
        $asignacionesActuales = $grupos->sum(fn (GrupoCup $grupo): int => $grupo->asignaciones->count());

        return [
            'inscripciones_confirmadas' => $inscripcionesConfirmadas,
            'capacidad_maxima' => GrupoCup::CAPACIDAD_MAXIMA,
            'grupos_necesarios' => $gruposNecesarios,
            'grupos_actuales' => $grupos->count(),
            'materias_activas' => $materiasActivas,
            'asignaciones_actuales' => $asignacionesActuales,
            'asignaciones_esperadas' => $asignacionesEsperadas,
            'planificacion_publicada' => $grupos->isNotEmpty() && $grupos->every(fn (GrupoCup $grupo): bool => $grupo->estaPublicado()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentarGrupos(GestionCup $gestionCup): array
    {
        return $gestionCup->grupos
            ->map(fn (GrupoCup $grupo): array => [
                'id_grupo_cup' => $grupo->id_grupo_cup,
                'nombre_grupo' => $grupo->nombre_grupo,
                'numero_grupo' => $grupo->numero_grupo,
                'capacidad_maxima' => $grupo->capacidad_maxima,
                'turno' => $grupo->turno,
                'estado' => $grupo->estado,
                'estado_label' => $grupo->estadoLabel(),
                'publicado_en' => $grupo->publicado_en?->format('Y-m-d H:i'),
                'inscripciones_count' => $grupo->inscripcionesAsignadas->count(),
                'asignaciones' => $grupo->asignaciones
                    ->sortBy(fn (AsignacionGrupo $asignacion): string => $asignacion->materiaCup?->nombre_materia ?? '')
                    ->map(fn (AsignacionGrupo $asignacion): array => $this->presentarAsignacion($asignacion))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarAsignacion(AsignacionGrupo $asignacion): array
    {
        $horario = $asignacion->horarioGrupoCup;

        return [
            'id_asignacion_grupo' => $asignacion->id_asignacion_grupo,
            'materia_cup_id' => $asignacion->materia_cup_id,
            'docente_id' => $asignacion->docente_id,
            'materia' => $asignacion->materiaCup?->nombre_materia,
            'docente' => $asignacion->docente?->nombreCompleto(),
            'dia_semana' => $horario?->dia_semana,
            'turno' => $horario?->turno,
            'hora_inicio' => $horario ? substr((string) $horario->hora_inicio, 0, 5) : null,
            'hora_fin' => $horario ? substr((string) $horario->hora_fin, 0, 5) : null,
            'modalidad' => $horario?->modalidad,
            'aula' => $horario?->aula,
            'enlace_clase' => $horario?->enlace_clase,
            'observacion' => $asignacion->observacion,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function opcionesGestiones(): array
    {
        return GestionCup::query()
            ->orderByDesc('id_gestion')
            ->get()
            ->map(fn (GestionCup $gestionCup): array => [
                'value' => (string) $gestionCup->id_gestion,
                'label' => "{$gestionCup->nombre_gestion} / {$gestionCup->convocatoria}",
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function opcionesMaterias(GestionCup $gestionCup): array
    {
        return $gestionCup->materias
            ->map(fn (MateriaCup $materia): array => [
                'value' => (string) $materia->id_materia_cup,
                'label' => $materia->nombre_materia,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function opcionesDocentes(GestionCup $gestionCup): array
    {
        return Docente::query()
            ->with(['materias' => fn ($query) => $query->where('gestion_cup_id', $gestionCup->id_gestion), 'disponibilidades'])
            ->where('estado_contratacion', Docente::ESTADO_HABILITADO)
            ->whereHas('materias', fn (Builder $query) => $query->where('gestion_cup_id', $gestionCup->id_gestion))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->map(fn (Docente $docente): array => [
                'value' => (string) $docente->id_docente,
                'label' => $docente->nombreCompleto(),
                'materias' => $docente->materias->pluck('id_materia_cup')->map(fn (int $id): string => (string) $id)->values()->all(),
                'disponibilidades' => $docente->disponibilidades
                    ->map(fn (DisponibilidadDocente $disponibilidad): array => [
                        'dia_semana' => $disponibilidad->dia_semana,
                        'turno' => $disponibilidad->turno,
                        'hora_inicio' => substr((string) $disponibilidad->hora_inicio, 0, 5),
                        'hora_fin' => substr((string) $disponibilidad->hora_fin, 0, 5),
                        'modalidad' => $disponibilidad->modalidad,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $opciones
     * @return list<array{value: string, label: string}>
     */
    private function mapearOpciones(array $opciones): array
    {
        return collect($opciones)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }
}
