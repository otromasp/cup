<?php

namespace App\Actions\CasosUso\CU08PlanificarGrupos;

use App\Models\DisponibilidadDocente;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\Inscripcion;
use App\Models\InscripcionGrupo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerarGruposCupAction
{
    /**
     * @return array{grupos_generados: int, inscripciones_asignadas: int}
     */
    public function execute(GestionCup $gestionCup): array
    {
        return DB::transaction(function () use ($gestionCup): array {
            $gestionCup = GestionCup::query()->lockForUpdate()->findOrFail($gestionCup->id_gestion);

            if ($gestionCup->grupos()->where('estado', GrupoCup::ESTADO_PUBLICADO)->exists()) {
                throw ValidationException::withMessages([
                    'gestion_cup_id' => 'No se puede regenerar una planificacion ya publicada.',
                ]);
            }

            $inscripciones = Inscripcion::query()
                ->where('gestion_cup_id', $gestionCup->id_gestion)
                ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
                ->orderBy('id_inscripcion')
                ->lockForUpdate()
                ->get(['id_inscripcion']);

            if ($inscripciones->isEmpty()) {
                throw ValidationException::withMessages([
                    'gestion_cup_id' => 'La gestion seleccionada no tiene inscripciones confirmadas para planificar.',
                ]);
            }

            // Regenerar limpia la planificacion previa para evitar cupos duplicados.
            $gestionCup->grupos()->delete();

            $cantidadGrupos = (int) ceil($inscripciones->count() / GrupoCup::CAPACIDAD_MAXIMA);
            $turnos = array_keys(DisponibilidadDocente::turnos());
            $asignadas = 0;

            for ($numero = 1; $numero <= $cantidadGrupos; $numero++) {
                $grupo = $gestionCup->grupos()->create([
                    'nombre_grupo' => 'Grupo '.$this->letraGrupo($numero),
                    'numero_grupo' => $numero,
                    'capacidad_maxima' => GrupoCup::CAPACIDAD_MAXIMA,
                    'turno' => $turnos[($numero - 1) % count($turnos)],
                    'estado' => GrupoCup::ESTADO_EN_PLANIFICACION,
                ]);

                $lote = $inscripciones
                    ->slice(($numero - 1) * GrupoCup::CAPACIDAD_MAXIMA, GrupoCup::CAPACIDAD_MAXIMA)
                    ->values();

                foreach ($lote as $inscripcion) {
                    $grupo->inscripcionesAsignadas()->create([
                        'inscripcion_id' => $inscripcion->id_inscripcion,
                        'estado' => InscripcionGrupo::ESTADO_ASIGNADA,
                        'asignado_en' => now(),
                    ]);

                    $asignadas++;
                }
            }

            return [
                'grupos_generados' => $cantidadGrupos,
                'inscripciones_asignadas' => $asignadas,
            ];
        });
    }

    private function letraGrupo(int $numero): string
    {
        $letras = '';

        while ($numero > 0) {
            $numero--;
            $letras = chr(65 + ($numero % 26)).$letras;
            $numero = intdiv($numero, 26);
        }

        return $letras;
    }
}
