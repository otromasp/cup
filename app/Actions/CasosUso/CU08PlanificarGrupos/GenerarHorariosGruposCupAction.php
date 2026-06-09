<?php

namespace App\Actions\CasosUso\CU08PlanificarGrupos;

use App\Models\AsignacionGrupo;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GenerarHorariosGruposCupAction
{
    private const DURACION_BLOQUE_MINUTOS = 120;

    public function __construct(
        private readonly GuardarAsignacionGrupoAction $guardarAsignacion,
    ) {}

    /**
     * @return array{asignaciones_generadas: int, asignaciones_pendientes: int}
     */
    public function execute(GestionCup $gestionCup): array
    {
        $gestionCup = GestionCup::query()
            ->with([
                'grupos.asignaciones',
                'materias' => fn ($query) => $query->where('estado', Usuario::ESTADO_ACTIVO)->orderBy('nombre_materia'),
            ])
            ->findOrFail($gestionCup->id_gestion);

        $grupos = $gestionCup->grupos->sortBy('numero_grupo')->values();
        $materias = $gestionCup->materias;

        $this->validarBase($grupos, $materias);

        $docentes = Docente::query()
            ->with(['materias', 'disponibilidades'])
            ->where('estado_contratacion', Docente::ESTADO_HABILITADO)
            ->whereHas('materias', fn ($query) => $query->where('gestion_cup_id', $gestionCup->id_gestion))
            ->get();

        if ($docentes->isEmpty()) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'No hay docentes habilitados para generar horarios.',
            ]);
        }

        $generadas = 0;
        $pendientes = 0;

        foreach ($grupos as $grupo) {
            foreach ($materias as $materia) {
                if ($this->yaTieneAsignacion($grupo, $materia)) {
                    continue;
                }

                if ($this->intentarAsignar($grupo, $materia, $docentes)) {
                    $generadas++;

                    continue;
                }

                $pendientes++;
            }
        }

        return [
            'asignaciones_generadas' => $generadas,
            'asignaciones_pendientes' => $pendientes,
        ];
    }

    /**
     * @param  Collection<int, GrupoCup>  $grupos
     * @param  Collection<int, MateriaCup>  $materias
     */
    private function validarBase(Collection $grupos, Collection $materias): void
    {
        if ($grupos->isEmpty()) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'Primero debe generar los grupos de la gestion.',
            ]);
        }

        if ($grupos->contains(fn (GrupoCup $grupo): bool => $grupo->estaPublicado())) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'No se puede generar horarios de una planificacion publicada.',
            ]);
        }

        if ($materias->isEmpty()) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'La gestion no tiene materias activas para generar horarios.',
            ]);
        }
    }

    private function yaTieneAsignacion(GrupoCup $grupo, MateriaCup $materia): bool
    {
        return $grupo->asignaciones->contains('materia_cup_id', $materia->id_materia_cup);
    }

    /**
     * @param  Collection<int, Docente>  $docentes
     */
    private function intentarAsignar(GrupoCup $grupo, MateriaCup $materia, Collection $docentes): bool
    {
        $candidatos = $docentes
            ->filter(fn (Docente $docente): bool => $docente->materias->contains('id_materia_cup', $materia->id_materia_cup))
            ->sortBy(fn (Docente $docente): string => sprintf('%04d-%04d', $this->cargaDocente($docente), $docente->id_docente))
            ->values();

        foreach ($candidatos as $docente) {
            foreach ($this->disponibilidadesOrdenadas($docente) as $disponibilidad) {
                foreach ($this->bloquesDisponibles($disponibilidad) as $bloque) {
                    try {
                        $this->guardarAsignacion->execute([
                            'grupo_cup_id' => $grupo->id_grupo_cup,
                            'materia_cup_id' => $materia->id_materia_cup,
                            'docente_id' => $docente->id_docente,
                            'dia_semana' => $disponibilidad->dia_semana,
                            'turno' => $disponibilidad->turno,
                            'hora_inicio' => $bloque['inicio'],
                            'hora_fin' => $bloque['fin'],
                            'modalidad' => $this->modalidadHorario($disponibilidad),
                            'aula' => $this->aula($grupo, $disponibilidad),
                            'enlace_clase' => $this->enlaceClase($grupo, $disponibilidad),
                            'observacion' => 'Asignacion generada automaticamente.',
                        ]);

                        $grupo->load('asignaciones');

                        return true;
                    } catch (ValidationException) {
                        continue;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return Collection<int, DisponibilidadDocente>
     */
    private function disponibilidadesOrdenadas(Docente $docente): Collection
    {
        $ordenDias = array_flip(array_keys(DisponibilidadDocente::diasSemana()));

        return $docente->disponibilidades
            ->sortBy(fn (DisponibilidadDocente $disponibilidad): string => sprintf(
                '%02d-%s',
                $ordenDias[$disponibilidad->dia_semana] ?? 99,
                $this->hora($disponibilidad->hora_inicio)
            ))
            ->values();
    }

    /**
     * @return list<array{inicio: string, fin: string}>
     */
    private function bloquesDisponibles(DisponibilidadDocente $disponibilidad): array
    {
        $inicio = $this->minutos($disponibilidad->hora_inicio);
        $fin = $this->minutos($disponibilidad->hora_fin);
        $bloques = [];

        for ($actual = $inicio; $actual + self::DURACION_BLOQUE_MINUTOS <= $fin; $actual += self::DURACION_BLOQUE_MINUTOS) {
            $bloques[] = [
                'inicio' => $this->formatearHora($actual),
                'fin' => $this->formatearHora($actual + self::DURACION_BLOQUE_MINUTOS),
            ];
        }

        return $bloques;
    }

    private function modalidadHorario(DisponibilidadDocente $disponibilidad): string
    {
        if ($disponibilidad->modalidad === DisponibilidadDocente::MODALIDAD_MIXTA) {
            return DisponibilidadDocente::MODALIDAD_PRESENCIAL;
        }

        return $disponibilidad->modalidad;
    }

    private function aula(GrupoCup $grupo, DisponibilidadDocente $disponibilidad): ?string
    {
        if ($disponibilidad->modalidad === DisponibilidadDocente::MODALIDAD_VIRTUAL) {
            return null;
        }

        return 'Aula '.$grupo->numero_grupo;
    }

    private function enlaceClase(GrupoCup $grupo, DisponibilidadDocente $disponibilidad): ?string
    {
        if ($disponibilidad->modalidad !== DisponibilidadDocente::MODALIDAD_VIRTUAL) {
            return null;
        }

        return 'https://meet.google.com/cup-grupo-'.$grupo->numero_grupo;
    }

    private function cargaDocente(Docente $docente): int
    {
        return AsignacionGrupo::query()
            ->where('docente_id', $docente->id_docente)
            ->distinct('grupo_cup_id')
            ->count('grupo_cup_id');
    }

    private function minutos(mixed $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $this->hora($hora)));

        return ($horas * 60) + $minutos;
    }

    private function formatearHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    private function hora(mixed $valor): string
    {
        return substr((string) $valor, 0, 5);
    }
}
