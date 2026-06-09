<?php

namespace App\Actions\CasosUso\CU06RealizarInscripcion;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\InscripcionRequisito;
use App\Models\PagoInscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrarInscripcionAction
{
    public function __construct(
        private readonly ConfirmarInscripcionAction $confirmarInscripcion
    ) {}

    /**
     * @param  array{
     *     gestion_cup_id: int,
     *     postulante: array<string, mixed>,
     *     turno_gestion_cup_id: int,
     *     carrera_primera_id: int,
     *     carrera_segunda_id: int|null,
     *     requisitos_cumplidos: list<int>
     * }  $datos
     * @param  array{session_id: string, payment_intent: string|null}|null  $pagoConfirmado
     */
    public function execute(array $datos, ?array $pagoConfirmado = null): Inscripcion
    {
        return DB::transaction(function () use ($datos, $pagoConfirmado): Inscripcion {
            $gestionCup = GestionCup::query()
                ->with(['requisitos', 'etapas'])
                ->lockForUpdate()
                ->findOrFail($datos['gestion_cup_id']);

            $this->validarInscripcionAbierta($gestionCup);

            $requierePago = $this->requierePago($gestionCup, (bool) $datos['postulante']['es_extranjero']);
            $turnoGestionCup = $this->turnoDisponible($gestionCup, (int) $datos['turno_gestion_cup_id']);

            if ($requierePago && $pagoConfirmado === null) {
                throw ValidationException::withMessages([
                    'pago' => 'La inscripcion requiere pago confirmado antes de guardar los datos.',
                ]);
            }

            $postulante = Postulante::query()->updateOrCreate(
                ['ci' => $datos['postulante']['ci']],
                $datos['postulante']
            );

            $inscripcion = Inscripcion::query()
                ->firstOrNew([
                    'gestion_cup_id' => $gestionCup->id_gestion,
                    'postulante_id' => $postulante->id_postulante,
                ]);

            if ($inscripcion->exists && $inscripcion->estaConfirmada()) {
                throw ValidationException::withMessages([
                    'ci' => 'El postulante ya tiene una inscripcion confirmada en esta gestion CUP.',
                ]);
            }

            $inscripcion->forceFill([
                'carrera_primera_id' => $datos['carrera_primera_id'],
                'carrera_segunda_id' => $datos['carrera_segunda_id'],
                'turno_gestion_cup_id' => $turnoGestionCup->id_turno_gestion,
                'codigo_inscripcion' => $inscripcion->codigo_inscripcion ?: $this->generarCodigoInscripcion(),
                'estado' => $requierePago ? Inscripcion::ESTADO_PENDIENTE_PAGO : Inscripcion::ESTADO_CONFIRMADA,
                'fecha_inscripcion' => $requierePago ? null : now(),
                'observacion' => null,
            ])->save();

            $inscripcion->requisitosCumplidos()->delete();
            $this->registrarRequisitos($inscripcion, $gestionCup, $datos['requisitos_cumplidos'], (bool) $datos['postulante']['es_extranjero']);

            if ($requierePago) {
                $this->prepararPago($inscripcion, $gestionCup);

                return $this->confirmarInscripcion->execute(
                    $inscripcion,
                    $pagoConfirmado['session_id'],
                    $pagoConfirmado['payment_intent']
                );
            }

            $inscripcion->pago()->delete();

            return $this->confirmarInscripcion->execute($inscripcion)
                ->load(['postulante', 'gestionCup', 'pago', 'requisitosCumplidos.requisito']);
        });
    }

    private function requierePago(GestionCup $gestionCup, bool $esExtranjero): bool
    {
        return $gestionCup->requisitos
            ->filter(fn (RequisitoInscripcion $requisito): bool => $this->requisitoAplica($requisito, $esExtranjero))
            ->contains(fn (RequisitoInscripcion $requisito): bool => $requisito->obligatorio
                && $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO
                && $requisito->estado === Usuario::ESTADO_ACTIVO);
    }

    private function validarInscripcionAbierta(GestionCup $gestionCup): void
    {
        if (! in_array($gestionCup->estado_configuracion, [GestionCup::ESTADO_CONFIGURADA, GestionCup::ESTADO_BLOQUEADA], true)) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'La gestion CUP todavia no esta habilitada para inscripcion.',
            ]);
        }

        if ($gestionCup->etapas->isEmpty()) {
            return;
        }

        $inscripcionActiva = $gestionCup->etapas
            ->contains(fn (EtapaGestionCup $etapa): bool => $etapa->estado_etapa === EtapaGestionCup::ESTADO_ACTIVA
                && str_contains(mb_strtolower($etapa->nombre_etapa), 'inscrip'));

        if (! $inscripcionActiva) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'La etapa de inscripcion no esta activa.',
            ]);
        }
    }

    private function turnoDisponible(GestionCup $gestionCup, int $turnoId): TurnoGestionCup
    {
        $turnoGestionCup = TurnoGestionCup::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->whereKey($turnoId)
            ->lockForUpdate()
            ->first();

        if (! $turnoGestionCup instanceof TurnoGestionCup) {
            throw ValidationException::withMessages([
                'turno_gestion_cup_id' => 'Debe seleccionar un turno disponible de la gestion CUP.',
            ]);
        }

        $ocupados = Inscripcion::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->where('turno_gestion_cup_id', $turnoGestionCup->id_turno_gestion)
            ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
            ->count();

        if ($ocupados >= $turnoGestionCup->capacidad_maxima) {
            throw ValidationException::withMessages([
                'turno_gestion_cup_id' => 'El turno seleccionado ya no tiene cupos disponibles.',
            ]);
        }

        return $turnoGestionCup;
    }

    /**
     * @param  list<int>  $requisitosCumplidos
     */
    private function registrarRequisitos(Inscripcion $inscripcion, GestionCup $gestionCup, array $requisitosCumplidos, bool $esExtranjero): void
    {
        $seleccionados = collect($requisitosCumplidos);

        $gestionCup->requisitos
            ->filter(fn (RequisitoInscripcion $requisito): bool => $requisito->estado === Usuario::ESTADO_ACTIVO)
            ->filter(fn (RequisitoInscripcion $requisito): bool => $this->requisitoAplica($requisito, $esExtranjero))
            ->each(function (RequisitoInscripcion $requisito) use ($inscripcion, $seleccionados): void {
                $esPago = $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO;
                $cumplido = ! $esPago && $seleccionados->contains($requisito->id_requisito);

                $inscripcion->requisitosCumplidos()->create([
                    'requisito_id' => $requisito->id_requisito,
                    'cumplido' => $cumplido,
                    'origen' => $esPago ? InscripcionRequisito::ORIGEN_PAGO : InscripcionRequisito::ORIGEN_DECLARATIVO,
                    'cumplido_en' => $cumplido ? now() : null,
                ]);
            });
    }

    private function prepararPago(Inscripcion $inscripcion, GestionCup $gestionCup): void
    {
        $montoCentavos = $gestionCup->montoInscripcionCentavos();

        if ($montoCentavos <= 0) {
            throw ValidationException::withMessages([
                'pago' => 'La gestion CUP debe tener un costo de inscripcion mayor a cero.',
            ]);
        }

        $inscripcion->pago()->updateOrCreate([], [
            'proveedor' => PagoInscripcion::PROVEEDOR_STRIPE,
            'monto_centavos' => $montoCentavos,
            'moneda' => $gestionCup->monedaInscripcionStripe(),
            'estado' => PagoInscripcion::ESTADO_PENDIENTE,
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
            'codigo_comprobante' => null,
            'pagado_en' => null,
        ]);
    }

    private function requisitoAplica(RequisitoInscripcion $requisito, bool $esExtranjero): bool
    {
        return $requisito->aplica_a === RequisitoInscripcion::APLICA_TODOS
            || ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && $esExtranjero);
    }

    private function generarCodigoInscripcion(): string
    {
        do {
            $codigo = 'CUP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Inscripcion::query()->where('codigo_inscripcion', $codigo)->exists());

        return $codigo;
    }
}
