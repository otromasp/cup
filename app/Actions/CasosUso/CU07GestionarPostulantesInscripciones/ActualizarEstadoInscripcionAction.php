<?php

namespace App\Actions\CasosUso\CU07GestionarPostulantesInscripciones;

use App\Actions\CasosUso\CU06RealizarInscripcion\ConfirmarInscripcionAction;
use App\Models\Inscripcion;
use App\Models\PagoInscripcion;
use App\Models\RequisitoInscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarEstadoInscripcionAction
{
    public function __construct(
        private readonly ConfirmarInscripcionAction $confirmarInscripcion
    ) {}

    /**
     * @param  array{estado: string, observacion: ?string}  $datos
     */
    public function execute(Inscripcion $inscripcion, array $datos): Inscripcion
    {
        return DB::transaction(function () use ($inscripcion, $datos): Inscripcion {
            $inscripcion = Inscripcion::query()
                ->with(['gestionCup.requisitos', 'postulante.usuario', 'pago', 'requisitosCumplidos.requisito'])
                ->lockForUpdate()
                ->findOrFail($inscripcion->id_inscripcion);

            if ($datos['estado'] === Inscripcion::ESTADO_CONFIRMADA) {
                $this->validarConfirmacion($inscripcion);

                $inscripcion = $this->confirmarInscripcion->execute($inscripcion);
            }

            if ($datos['estado'] === Inscripcion::ESTADO_CANCELADA) {
                $this->cancelarInscripcion($inscripcion);
            }

            $inscripcion->forceFill([
                'estado' => $datos['estado'],
                'observacion' => $datos['observacion'],
            ])->save();

            return $inscripcion->load(['gestionCup', 'postulante.usuario', 'carreraPrimera', 'carreraSegunda', 'pago', 'requisitosCumplidos.requisito']);
        });
    }

    private function validarConfirmacion(Inscripcion $inscripcion): void
    {
        $requisitosObligatorios = $inscripcion->gestionCup?->requisitos
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->where('obligatorio', true) ?? collect();

        foreach ($requisitosObligatorios as $requisito) {
            if (! $this->requisitoAplica($inscripcion, $requisito)) {
                continue;
            }

            if ($requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO) {
                if ($inscripcion->pago?->estado !== PagoInscripcion::ESTADO_PAGADO) {
                    throw ValidationException::withMessages([
                        'estado' => 'No se puede confirmar la inscripcion porque el pago no esta verificado.',
                    ]);
                }

                continue;
            }

            $cumplido = $inscripcion->requisitosCumplidos
                ->where('requisito_id', $requisito->id_requisito)
                ->where('cumplido', true)
                ->isNotEmpty();

            if (! $cumplido) {
                throw ValidationException::withMessages([
                    'estado' => "No se puede confirmar la inscripcion porque falta el requisito: {$requisito->nombre_requisito}.",
                ]);
            }
        }
    }

    private function cancelarInscripcion(Inscripcion $inscripcion): void
    {
        if ($inscripcion->pago?->estado === PagoInscripcion::ESTADO_PENDIENTE) {
            $inscripcion->pago->forceFill([
                'estado' => PagoInscripcion::ESTADO_CANCELADO,
            ])->save();
        }

        $usuario = $inscripcion->postulante?->usuario;

        if ($usuario instanceof Usuario && $usuario->estado !== Usuario::ESTADO_INACTIVO) {
            $usuario->forceFill([
                'estado' => Usuario::ESTADO_INACTIVO,
            ])->save();
        }
    }

    private function requisitoAplica(Inscripcion $inscripcion, RequisitoInscripcion $requisito): bool
    {
        return $requisito->aplica_a === RequisitoInscripcion::APLICA_TODOS
            || ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && (bool) $inscripcion->postulante?->es_extranjero);
    }
}
