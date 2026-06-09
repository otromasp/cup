<?php

namespace App\Actions\CasosUso\CU05GestionarDocente;

use App\Models\Docente;
use Illuminate\Support\Facades\DB;

class GuardarDocenteAction
{
    /**
     * @param  array{
     *     docente: array<string, mixed>,
     *     materias: list<int>,
     *     disponibilidades: list<array<string, mixed>>
     * }  $datos
     */
    public function execute(array $datos, ?Docente $docente = null): Docente
    {
        return DB::transaction(function () use ($datos, $docente): Docente {
            $docente ??= new Docente;

            $docente->forceFill($datos['docente'])->save();

            $docente->materias()->sync($datos['materias']);

            // Referencia administrativa: no define el horario final de grupos.
            $docente->disponibilidades()->delete();
            $docente->disponibilidades()->createMany($datos['disponibilidades']);

            return $docente->load(['usuario', 'materias.gestionCup', 'disponibilidades']);
        });
    }
}
