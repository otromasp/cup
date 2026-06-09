<?php

namespace App\Http\Controllers\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Actions\CasosUso\CU04GestionarEtapasGestionCUP\ActivarEtapaGestionCupAction;
use App\Actions\CasosUso\CU04GestionarEtapasGestionCUP\CerrarEtapaGestionCupAction;
use App\Actions\CasosUso\CU04GestionarEtapasGestionCUP\GuardarEtapasGestionCupAction;
use App\Actions\CasosUso\CU04GestionarEtapasGestionCUP\ReabrirEtapaGestionCupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU04GestionarEtapasGestionCUP\GuardarEtapasGestionCupRequest;
use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ControladorEtapasGestion extends Controller
{
    /**
     * @var list<string>
     */
    private const ETAPAS_PREDETERMINADAS = [
        'Inscripcion',
        'Planificacion academica',
        'Desarrollo de clases',
        'Registro de notas',
        'Consolidacion y admision',
        'Publicacion de resultados',
    ];

    public function index(Request $request): Response
    {
        $gestiones = GestionCup::query()
            ->with('etapas')
            ->withCount('etapas')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn (GestionCup $gestionCup): array => $this->presentarResumen($gestionCup));

        return Inertia::render('casos-uso/cu04-gestionar-etapas-cup/index', [
            'gestiones' => $gestiones,
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]);
    }

    public function edit(Request $request, GestionCup $gestionCup): Response
    {
        $gestionCup->load('etapas');

        return Inertia::render('casos-uso/cu04-gestionar-etapas-cup/edit', [
            'gestion' => $this->presentarGestion($gestionCup),
            'etapas' => $gestionCup->etapas->isNotEmpty()
                ? $gestionCup->etapas->map(fn (EtapaGestionCup $etapaGestionCup): array => $this->presentarEtapa($etapaGestionCup))->values()
                : $this->etapasPredeterminadas($gestionCup),
            'opciones' => [
                'estadosEtapa' => $this->mapearOpciones(EtapaGestionCup::estadosEtapa()),
            ],
            'puedeProgramar' => $this->puedeProgramar($gestionCup),
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]);
    }

    public function update(
        GuardarEtapasGestionCupRequest $request,
        GestionCup $gestionCup,
        GuardarEtapasGestionCupAction $guardarEtapasGestionCup
    ): RedirectResponse {
        $guardarEtapasGestionCup->execute($gestionCup, $request->datosEtapas());

        return back()->with('status', 'Etapas de la gestion CUP programadas correctamente.');
    }

    public function activate(EtapaGestionCup $etapaGestionCup, ActivarEtapaGestionCupAction $activarEtapaGestionCup): RedirectResponse
    {
        $activarEtapaGestionCup->execute($etapaGestionCup);

        return to_route('cu04.etapas-cup.edit', $etapaGestionCup->gestion_cup_id)
            ->with('status', 'Avance registrado. La etapa seleccionada queda activa y la anterior queda cerrada.');
    }

    public function close(EtapaGestionCup $etapaGestionCup, CerrarEtapaGestionCupAction $cerrarEtapaGestionCup): RedirectResponse
    {
        $cerrarEtapaGestionCup->execute($etapaGestionCup);

        return to_route('cu04.etapas-cup.edit', $etapaGestionCup->gestion_cup_id)
            ->with('status', 'Etapa finalizada.');
    }

    public function reopen(EtapaGestionCup $etapaGestionCup, ReabrirEtapaGestionCupAction $reabrirEtapaGestionCup): RedirectResponse
    {
        $reabrirEtapaGestionCup->execute($etapaGestionCup);

        return to_route('cu04.etapas-cup.edit', $etapaGestionCup->gestion_cup_id)
            ->with('status', 'Etapa retomada. Las etapas posteriores vuelven a quedar programadas.');
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
    private function presentarGestion(GestionCup $gestionCup): array
    {
        return [
            'id_gestion' => $gestionCup->id_gestion,
            'nombre_gestion' => $gestionCup->nombre_gestion,
            'convocatoria' => $gestionCup->convocatoria,
            'fecha_inicio' => $gestionCup->fecha_inicio?->toDateString(),
            'fecha_fin' => $gestionCup->fecha_fin?->toDateString(),
            'estado_configuracion' => $gestionCup->estado_configuracion,
            'estado_configuracion_label' => GestionCup::estadosConfiguracion()[$gestionCup->estado_configuracion] ?? $gestionCup->estado_configuracion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarResumen(GestionCup $gestionCup): array
    {
        $etapaActiva = $gestionCup->etapas->firstWhere('estado_etapa', EtapaGestionCup::ESTADO_ACTIVA);

        return [
            'id_gestion' => $gestionCup->id_gestion,
            'nombre_gestion' => $gestionCup->nombre_gestion,
            'convocatoria' => $gestionCup->convocatoria,
            'fecha_inicio' => $gestionCup->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $gestionCup->fecha_fin?->format('Y-m-d'),
            'estado_configuracion' => $gestionCup->estado_configuracion,
            'estado_configuracion_label' => GestionCup::estadosConfiguracion()[$gestionCup->estado_configuracion] ?? $gestionCup->estado_configuracion,
            'etapas_count' => $gestionCup->etapas_count,
            'etapa_activa' => $etapaActiva?->nombre_etapa,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarEtapa(EtapaGestionCup $etapaGestionCup): array
    {
        return [
            'id_etapa_gestion' => $etapaGestionCup->id_etapa_gestion,
            'nombre_etapa' => $etapaGestionCup->nombre_etapa,
            'orden' => (string) $etapaGestionCup->orden,
            'fecha_inicio' => $etapaGestionCup->fecha_inicio?->toDateString(),
            'fecha_fin' => $etapaGestionCup->fecha_fin?->toDateString(),
            'estado_etapa' => $etapaGestionCup->estado_etapa,
            'estado_etapa_label' => EtapaGestionCup::estadosEtapa()[$etapaGestionCup->estado_etapa] ?? $etapaGestionCup->estado_etapa,
        ];
    }

    private function puedeProgramar(GestionCup $gestionCup): bool
    {
        return ! $gestionCup->etapas
            ->contains(fn (EtapaGestionCup $etapaGestionCup): bool => in_array($etapaGestionCup->estado_etapa, [
                EtapaGestionCup::ESTADO_ACTIVA,
                EtapaGestionCup::ESTADO_CERRADA,
            ], true));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function etapasPredeterminadas(GestionCup $gestionCup): Collection
    {
        $inicioGestion = $gestionCup->fecha_inicio ?? Carbon::now();
        $finGestion = $gestionCup->fecha_fin ?? $inicioGestion->copy()->addMonths(2);
        $cantidadEtapas = count(self::ETAPAS_PREDETERMINADAS);
        $diasTotales = max($inicioGestion->diffInDays($finGestion) + 1, $cantidadEtapas);
        $diasBase = intdiv($diasTotales, $cantidadEtapas);
        $diasExtra = $diasTotales % $cantidadEtapas;
        $cursor = $inicioGestion->copy();

        return collect(self::ETAPAS_PREDETERMINADAS)
            ->map(function (string $nombreEtapa, int $indice) use (&$cursor, $finGestion, $diasBase, $diasExtra, $cantidadEtapas): array {
                $duracion = $diasBase + ($indice < $diasExtra ? 1 : 0);
                $inicioEtapa = $cursor->copy();
                $finEtapa = $indice === $cantidadEtapas - 1
                    ? $finGestion->copy()
                    : $cursor->copy()->addDays(max($duracion - 1, 0));
                $cursor = $finEtapa->copy()->addDay();

                return [
                    'id_etapa_gestion' => null,
                    'nombre_etapa' => $nombreEtapa,
                    'orden' => (string) ($indice + 1),
                    'fecha_inicio' => $inicioEtapa->toDateString(),
                    'fecha_fin' => $finEtapa->toDateString(),
                    'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
                    'estado_etapa_label' => EtapaGestionCup::estadosEtapa()[EtapaGestionCup::ESTADO_PROGRAMADA],
                ];
            });
    }
}
