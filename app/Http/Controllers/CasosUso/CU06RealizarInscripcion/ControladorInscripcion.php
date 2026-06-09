<?php

namespace App\Http\Controllers\CasosUso\CU06RealizarInscripcion;

use App\Actions\CasosUso\CU06RealizarInscripcion\ConfirmarPagoStripeAction;
use App\Actions\CasosUso\CU06RealizarInscripcion\CrearCheckoutInscripcionAction;
use App\Actions\CasosUso\CU06RealizarInscripcion\RegistrarInscripcionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU06RealizarInscripcion\GuardarInscripcionRequest;
use App\Models\CarreraCup;
use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ControladorInscripcion extends Controller
{
    public function create(Request $request): Response
    {
        $gestionCup = $this->gestionDisponible();

        return Inertia::render('casos-uso/cu06-realizar-inscripcion/create', [
            'gestion' => $gestionCup ? $this->presentarGestion($gestionCup) : null,
            'turnos' => $gestionCup ? $this->presentarTurnos($gestionCup) : [],
            'carreras' => $gestionCup ? $this->presentarCarreras($gestionCup) : [],
            'requisitos' => $gestionCup ? $this->presentarRequisitos($gestionCup) : [],
            'montoPago' => $this->presentarMontoPago($gestionCup),
            'datosPrevios' => $request->session()->pull('cu06.inscripcion.datos_previos'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(
        GuardarInscripcionRequest $request,
        RegistrarInscripcionAction $registrarInscripcion,
        CrearCheckoutInscripcionAction $crearCheckout
    ): SymfonyResponse|RedirectResponse {
        $datos = $request->datosInscripcion();

        if ($this->requierePago($datos)) {
            $urlCheckout = $crearCheckout->execute($datos);

            return Inertia::location($urlCheckout);
        }

        $inscripcion = $registrarInscripcion->execute($datos);

        return to_route('cu06.inscripcion.resultado', $inscripcion)
            ->with('status', 'Inscripcion confirmada correctamente.');
    }

    public function paymentSuccess(
        Request $request,
        ConfirmarPagoStripeAction $confirmarPago
    ): RedirectResponse {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return to_route('cu06.inscripcion.create')
                ->with('status', 'No se recibio la referencia de pago de Stripe.');
        }

        try {
            $inscripcion = $confirmarPago->execute($sessionId);

            return to_route('cu06.inscripcion.resultado', $inscripcion)
                ->with('status', 'Pago verificado e inscripcion confirmada.');
        } catch (ValidationException $exception) {
            return to_route('cu06.inscripcion.create')
                ->with('status', collect($exception->errors())->flatten()->first() ?: 'No se pudo verificar el pago.');
        } catch (Throwable) {
            return to_route('cu06.inscripcion.create')
                ->with('status', 'No se pudo verificar el pago con Stripe en este momento.');
        }
    }

    public function paymentCanceled(Request $request): RedirectResponse
    {
        $tokenBorrador = (string) $request->query('borrador', '');
        $datos = $tokenBorrador !== '' ? Cache::pull(CrearCheckoutInscripcionAction::cacheKey($tokenBorrador)) : null;

        if (is_array($datos)) {
            session()->put('cu06.inscripcion.datos_previos', $this->prepararDatosPrevios($datos));
        }

        return to_route('cu06.inscripcion.create')
            ->with('status', 'El pago fue cancelado. Revise los datos y vuelva a continuar con el pago.');
    }

    public function result(Request $request, Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['gestionCup', 'postulante.usuario', 'carreraPrimera', 'carreraSegunda', 'turnoGestionCup', 'pago', 'requisitosCumplidos.requisito']);

        return Inertia::render('casos-uso/cu06-realizar-inscripcion/resultado', [
            'inscripcion' => $this->presentarInscripcion($inscripcion),
            'status' => $request->session()->get('status'),
        ]);
    }

    private function gestionDisponible(): ?GestionCup
    {
        return GestionCup::query()
            ->with(['carreras', 'requisitos', 'turnos', 'etapas'])
            ->whereIn('estado_configuracion', [GestionCup::ESTADO_CONFIGURADA, GestionCup::ESTADO_BLOQUEADA])
            ->orderByDesc('id_gestion')
            ->get()
            ->first(fn (GestionCup $gestionCup): bool => $this->inscripcionAbierta($gestionCup));
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
     * @return Collection<int, array<string, mixed>>
     */
    private function presentarCarreras(GestionCup $gestionCup): Collection
    {
        return $gestionCup->carreras
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->map(fn (CarreraCup $carrera): array => [
                'value' => (string) $carrera->id_carrera_cup,
                'label' => $carrera->nombre_carrera,
                'cupo_disponible' => max(0, $carrera->cupo_disponible - $carrera->cupo_ocupado),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function presentarTurnos(GestionCup $gestionCup): Collection
    {
        $ocupados = Inscripcion::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
            ->whereNotNull('turno_gestion_cup_id')
            ->selectRaw('turno_gestion_cup_id, count(*) as total')
            ->groupBy('turno_gestion_cup_id')
            ->pluck('total', 'turno_gestion_cup_id');

        return $gestionCup->turnos
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->map(function (TurnoGestionCup $turnoGestionCup) use ($ocupados): array {
                $ocupado = (int) ($ocupados[$turnoGestionCup->id_turno_gestion] ?? 0);

                return [
                    'value' => (string) $turnoGestionCup->id_turno_gestion,
                    'label' => TurnoGestionCup::turnos()[$turnoGestionCup->turno] ?? $turnoGestionCup->turno,
                    'modalidad' => TurnoGestionCup::modalidades()[$turnoGestionCup->modalidad] ?? $turnoGestionCup->modalidad,
                    'cupo_disponible' => max(0, $turnoGestionCup->capacidad_maxima - $ocupado),
                ];
            })
            ->filter(fn (array $turno): bool => $turno['cupo_disponible'] > 0)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function presentarRequisitos(GestionCup $gestionCup): Collection
    {
        return $gestionCup->requisitos
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->map(fn (RequisitoInscripcion $requisito): array => [
                'id_requisito' => $requisito->id_requisito,
                'nombre_requisito' => $requisito->nombre_requisito,
                'obligatorio' => (bool) $requisito->obligatorio,
                'tipo_requisito' => $requisito->tipo_requisito,
                'aplica_a' => $requisito->aplica_a,
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarMontoPago(?GestionCup $gestionCup): array
    {
        if (! $gestionCup instanceof GestionCup) {
            return [
                'monto_centavos' => 0,
                'moneda' => GestionCup::MONEDA_BOB,
                'label' => 'BOB 0.00',
            ];
        }

        $montoCentavos = $gestionCup->montoInscripcionCentavos();
        $moneda = strtoupper($gestionCup->monedaInscripcionStripe());

        return [
            'monto_centavos' => $montoCentavos,
            'moneda' => $moneda,
            'label' => $moneda.' '.number_format($montoCentavos / 100, 2, '.', ','),
        ];
    }

    /**
     * @param  array{
     *     gestion_cup_id: int,
     *     postulante: array<string, mixed>,
     *     turno_gestion_cup_id: int,
     *     carrera_primera_id: int,
     *     carrera_segunda_id: int|null,
     *     requisitos_cumplidos: list<int>
     * }  $datos
     */
    private function requierePago(array $datos): bool
    {
        $gestionCup = GestionCup::query()
            ->with('requisitos')
            ->find($datos['gestion_cup_id']);

        if (! $gestionCup instanceof GestionCup) {
            return false;
        }

        return $gestionCup->requisitos
            ->filter(fn (RequisitoInscripcion $requisito): bool => $this->requisitoAplica($requisito, (bool) $datos['postulante']['es_extranjero']))
            ->contains(fn (RequisitoInscripcion $requisito): bool => $requisito->obligatorio
                && $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO
                && $requisito->estado === Usuario::ESTADO_ACTIVO);
    }

    private function requisitoAplica(RequisitoInscripcion $requisito, bool $esExtranjero): bool
    {
        return $requisito->aplica_a === RequisitoInscripcion::APLICA_TODOS
            || ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && $esExtranjero);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarInscripcion(Inscripcion $inscripcion): array
    {
        return [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'codigo_inscripcion' => $inscripcion->codigo_inscripcion,
            'estado' => $inscripcion->estado,
            'fecha_inscripcion' => $inscripcion->fecha_inscripcion?->format('Y-m-d H:i'),
            'gestion' => $inscripcion->gestionCup?->only(['nombre_gestion', 'convocatoria']),
            'postulante' => [
                'nombre_completo' => $inscripcion->postulante?->nombreCompleto(),
                'ci' => $inscripcion->postulante?->ci,
                'correo' => $inscripcion->postulante?->correo,
                'usuario_id' => $inscripcion->postulante?->usuario_id,
            ],
            'carrera_primera' => $inscripcion->carreraPrimera?->nombre_carrera,
            'carrera_segunda' => $inscripcion->carreraSegunda?->nombre_carrera,
            'turno' => $inscripcion->turnoGestionCup
                ? (TurnoGestionCup::turnos()[$inscripcion->turnoGestionCup->turno] ?? $inscripcion->turnoGestionCup->turno)
                : null,
            'pago' => $inscripcion->pago ? [
                'estado' => $inscripcion->pago->estado,
                'codigo_comprobante' => $inscripcion->pago->codigo_comprobante,
                'pagado_en' => $inscripcion->pago->pagado_en?->format('Y-m-d H:i'),
            ] : null,
            'requisitos' => $inscripcion->requisitosCumplidos
                ->map(fn ($cumplimiento): array => [
                    'nombre_requisito' => $cumplimiento->requisito?->nombre_requisito,
                    'cumplido' => (bool) $cumplimiento->cumplido,
                    'origen' => $cumplimiento->origen,
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prepararDatosPrevios(array $datos): array
    {
        return [
            'gestion_cup_id' => (string) $datos['gestion_cup_id'],
            'ci' => (string) $datos['postulante']['ci'],
            'nombres' => (string) $datos['postulante']['nombres'],
            'apellidos' => (string) $datos['postulante']['apellidos'],
            'correo' => (string) $datos['postulante']['correo'],
            'telefono' => (string) ($datos['postulante']['telefono'] ?? ''),
            'colegio_procedencia' => (string) ($datos['postulante']['colegio_procedencia'] ?? ''),
            'anio_bachillerato' => filled($datos['postulante']['anio_bachillerato'] ?? null) ? (string) $datos['postulante']['anio_bachillerato'] : '',
            'es_extranjero' => (bool) $datos['postulante']['es_extranjero'],
            'turno_gestion_cup_id' => (string) ($datos['turno_gestion_cup_id'] ?? ''),
            'carrera_primera_id' => (string) $datos['carrera_primera_id'],
            'carrera_segunda_id' => $datos['carrera_segunda_id'] ? (string) $datos['carrera_segunda_id'] : '',
            'requisitos_cumplidos' => collect($datos['requisitos_cumplidos'])
                ->map(fn (mixed $id): string => (string) $id)
                ->values()
                ->all(),
        ];
    }

    private function inscripcionAbierta(GestionCup $gestionCup): bool
    {
        if ($gestionCup->etapas->isEmpty()) {
            return $gestionCup->estado_configuracion === GestionCup::ESTADO_CONFIGURADA;
        }

        return $gestionCup->etapas
            ->contains(fn (EtapaGestionCup $etapa): bool => $etapa->estado_etapa === EtapaGestionCup::ESTADO_ACTIVA
                && str_contains(mb_strtolower($etapa->nombre_etapa), 'inscrip'));
    }
}
