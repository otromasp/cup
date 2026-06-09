<?php

namespace App\Actions\CasosUso\CU08PlanificarGrupos;

use App\Models\AsignacionGrupo;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\GrupoCup;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarAsignacionGrupoAction
{
    /**
     * @param  array{
     *     grupo_cup_id: int,
     *     materia_cup_id: int,
     *     docente_id: int,
     *     dia_semana: string,
     *     turno: string,
     *     hora_inicio: string,
     *     hora_fin: string,
     *     modalidad: string,
     *     aula: ?string,
     *     enlace_clase: ?string,
     *     observacion: ?string
     * }  $datos
     */
    public function execute(array $datos): AsignacionGrupo
    {
        return DB::transaction(function () use ($datos): AsignacionGrupo {
            $grupo = GrupoCup::query()
                ->with('gestionCup')
                ->lockForUpdate()
                ->findOrFail($datos['grupo_cup_id']);

            if ($grupo->estaPublicado()) {
                throw ValidationException::withMessages([
                    'grupo_cup_id' => 'No se puede modificar un grupo ya publicado.',
                ]);
            }

            $materia = MateriaCup::query()->findOrFail($datos['materia_cup_id']);
            $docente = Docente::query()->with(['materias', 'disponibilidades'])->findOrFail($datos['docente_id']);
            $asignacion = AsignacionGrupo::query()
                ->with('horarioGrupoCup')
                ->where('grupo_cup_id', $grupo->id_grupo_cup)
                ->where('materia_cup_id', $materia->id_materia_cup)
                ->first();

            $this->validarMateria($grupo, $materia);
            $this->validarDocente($docente, $materia);
            $this->validarDisponibilidad($docente, $datos);
            $this->validarCruces($docente, $grupo, $datos, $asignacion);
            $this->validarLimiteDocente($docente, $grupo, $asignacion);

            $horario = $asignacion?->horarioGrupoCup ?? $grupo->horarios()->make();
            $horario->forceFill([
                'grupo_cup_id' => $grupo->id_grupo_cup,
                'dia_semana' => $datos['dia_semana'],
                'turno' => $datos['turno'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'modalidad' => $datos['modalidad'],
                'aula' => $datos['aula'],
                'enlace_clase' => $datos['enlace_clase'],
            ])->save();

            $asignacion ??= new AsignacionGrupo;
            $asignacion->forceFill([
                'grupo_cup_id' => $grupo->id_grupo_cup,
                'materia_cup_id' => $materia->id_materia_cup,
                'docente_id' => $docente->id_docente,
                'horario_grupo_cup_id' => $horario->id_horario_grupo_cup,
                'estado' => AsignacionGrupo::ESTADO_ASIGNADA,
                'observacion' => $datos['observacion'],
            ])->save();

            return $asignacion->load(['grupoCup', 'materiaCup', 'docente', 'horarioGrupoCup']);
        });
    }

    private function validarMateria(GrupoCup $grupo, MateriaCup $materia): void
    {
        if ($materia->gestion_cup_id !== $grupo->gestion_cup_id || $materia->estado !== Usuario::ESTADO_ACTIVO) {
            throw ValidationException::withMessages([
                'materia_cup_id' => 'La materia seleccionada no pertenece a la gestion del grupo o no esta activa.',
            ]);
        }
    }

    private function validarDocente(Docente $docente, MateriaCup $materia): void
    {
        if ($docente->estado_contratacion !== Docente::ESTADO_HABILITADO) {
            throw ValidationException::withMessages([
                'docente_id' => 'El docente debe estar habilitado para ser asignado.',
            ]);
        }

        $dictaMateria = $docente->materias->contains('id_materia_cup', $materia->id_materia_cup);

        if (! $dictaMateria) {
            throw ValidationException::withMessages([
                'docente_id' => 'El docente seleccionado no esta asociado a la materia.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function validarDisponibilidad(Docente $docente, array $datos): void
    {
        $disponible = $docente->disponibilidades->contains(function (DisponibilidadDocente $disponibilidad) use ($datos): bool {
            return $disponibilidad->dia_semana === $datos['dia_semana']
                && $disponibilidad->turno === $datos['turno']
                && $this->modalidadCompatible($disponibilidad->modalidad, (string) $datos['modalidad'])
                && $datos['hora_inicio'] >= $this->hora($disponibilidad->hora_inicio)
                && $datos['hora_fin'] <= $this->hora($disponibilidad->hora_fin);
        });

        if (! $disponible) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El horario no coincide con la disponibilidad declarada del docente.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function validarCruces(Docente $docente, GrupoCup $grupo, array $datos, ?AsignacionGrupo $asignacion): void
    {
        $horarioId = $asignacion?->horario_grupo_cup_id;

        $grupoCruzado = $grupo->horarios()
            ->when($horarioId, fn ($query) => $query->whereKeyNot($horarioId))
            ->where('dia_semana', $datos['dia_semana'])
            ->where('hora_inicio', '<', $datos['hora_fin'])
            ->where('hora_fin', '>', $datos['hora_inicio'])
            ->exists();

        if ($grupoCruzado) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El grupo ya tiene otro horario en ese bloque.',
            ]);
        }

        $docenteCruzado = AsignacionGrupo::query()
            ->where('docente_id', $docente->id_docente)
            ->when($asignacion, fn ($query) => $query->whereKeyNot($asignacion->id_asignacion_grupo))
            ->whereHas('horarioGrupoCup', function ($query) use ($datos): void {
                $query->where('dia_semana', $datos['dia_semana'])
                    ->where('hora_inicio', '<', $datos['hora_fin'])
                    ->where('hora_fin', '>', $datos['hora_inicio']);
            })
            ->exists();

        if ($docenteCruzado) {
            throw ValidationException::withMessages([
                'docente_id' => 'El docente ya tiene una asignacion que cruza con ese horario.',
            ]);
        }
    }

    private function validarLimiteDocente(Docente $docente, GrupoCup $grupo, ?AsignacionGrupo $asignacion): void
    {
        $docenteYaAsignadoAlGrupo = AsignacionGrupo::query()
            ->where('docente_id', $docente->id_docente)
            ->where('grupo_cup_id', $grupo->id_grupo_cup)
            ->when($asignacion, fn ($query) => $query->whereKeyNot($asignacion->id_asignacion_grupo))
            ->exists();

        if ($docenteYaAsignadoAlGrupo || $asignacion?->docente_id === $docente->id_docente) {
            return;
        }

        $gruposActuales = AsignacionGrupo::query()
            ->where('docente_id', $docente->id_docente)
            ->distinct('grupo_cup_id')
            ->count('grupo_cup_id');

        if ($gruposActuales >= $docente->maximo_grupos_asignables) {
            throw ValidationException::withMessages([
                'docente_id' => 'El docente alcanzo su maximo de grupos asignables.',
            ]);
        }
    }

    private function modalidadCompatible(string $modalidadDocente, string $modalidadHorario): bool
    {
        return $modalidadDocente === $modalidadHorario
            || $modalidadDocente === DisponibilidadDocente::MODALIDAD_MIXTA;
    }

    private function hora(mixed $valor): string
    {
        return substr((string) $valor, 0, 5);
    }
}
