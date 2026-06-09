<?php

namespace App\Actions\CasosUso\CU05GestionarDocente;

use App\Models\Docente;
use Illuminate\Validation\ValidationException;

class CambiarEstadoDocenteAction
{
    public function execute(Docente $docente, string $estadoContratacion): Docente
    {
        if ($estadoContratacion === Docente::ESTADO_HABILITADO && ! $docente->cumpleRequisitosHabilitacion()) {
            throw ValidationException::withMessages([
                'estado_contratacion' => 'Para habilitar al docente debe cumplir requisitos academicos, materias y disponibilidad.',
            ]);
        }

        $docente->forceFill([
            'estado_contratacion' => $estadoContratacion,
        ])->save();

        return $docente->load(['materias', 'disponibilidades']);
    }
}
