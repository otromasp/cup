<?php

namespace App\Http\Controllers\CasosUso\CU07GestionarPostulantesInscripciones;

use App\Actions\CasosUso\CU07GestionarPostulantesInscripciones\ActualizarEstadoInscripcionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU07GestionarPostulantesInscripciones\ActualizarEstadoInscripcionRequest;
use App\Models\CarreraCup;
use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\InscripcionRequisito;
use App\Models\PagoInscripcion;
use App\Models\Postulante;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ControladorPostulanteInscripcion extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'gestion_id' => trim((string) $request->query('gestion_id', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'carrera_id' => trim((string) $request->query('carrera_id', '')),
        ];

        $inscripciones = Inscripcion::query()
            ->with(['gestionCup', 'postulante.usuario', 'carreraPrimera', 'carreraSegunda', 'pago'])
            ->when($filtros['gestion_id'] !== '', fn (Builder $query) => $query->where('gestion_cup_id', (int) $filtros['gestion_id']))
            ->when($filtros['estado'] !== '', fn (Builder $query) => $query->where('estado', $filtros['estado']))
            ->when($filtros['carrera_id'] !== '', function (Builder $query) use ($filtros): void {
                $query->where(function (Builder $query) use ($filtros): void {
                    $query->where('carrera_primera_id', (int) $filtros['carrera_id'])
                        ->orWhere('carrera_segunda_id', (int) $filtros['carrera_id']);
                });
            })
            ->when($filtros['buscar'] !== '', fn (Builder $query) => $this->aplicarBusqueda($query, $filtros['buscar']))
            ->orderByDesc('fecha_inscripcion')
            ->orderByDesc('id_inscripcion')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Inscripcion $inscripcion): array => $this->presentarResumen($inscripcion));

        return Inertia::render('casos-uso/cu07-gestionar-postulantes-inscripciones/index', [
            'inscripciones' => $inscripciones,
            'filtros' => $filtros,
            'opciones' => [
                'gestiones' => $this->opcionesGestiones(),
                'carreras' => $this->opcionesCarreras($filtros['gestion_id']),
                'estadosInscripcion' => $this->mapearOpciones(Inscripcion::estadosGestionables()),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function show(Request $request, Inscripcion $inscripcion): Response
    {
        $inscripcion->load(['gestionCup', 'postulante.usuario', 'carreraPrimera', 'carreraSegunda', 'pago', 'requisitosCumplidos.requisito']);

        return Inertia::render('casos-uso/cu07-gestionar-postulantes-inscripciones/show', [
            'inscripcion' => $this->presentarDetalle($inscripcion),
            'opciones' => [
                'estadosInscripcion' => $this->mapearOpciones(Inscripcion::estadosGestionables()),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function updateStatus(
        ActualizarEstadoInscripcionRequest $request,
        Inscripcion $inscripcion,
        ActualizarEstadoInscripcionAction $actualizarEstado
    ): RedirectResponse {
        $actualizarEstado->execute($inscripcion, $request->datosEstado());

        return back()->with('status', 'Inscripcion actualizada correctamente.');
    }

    private function aplicarBusqueda(Builder $query, string $buscar): void
    {
        $termino = '%'.mb_strtolower($buscar).'%';

        $query->where(function (Builder $query) use ($termino): void {
            $query->whereRaw('LOWER(codigo_inscripcion) LIKE ?', [$termino])
                ->orWhereHas('postulante', function (Builder $query) use ($termino): void {
                    $query->whereRaw('LOWER(ci) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(nombres) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(apellidos) LIKE ?', [$termino])
                        ->orWhereRaw('LOWER(correo) LIKE ?', [$termino]);
                });
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarResumen(Inscripcion $inscripcion): array
    {
        return [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'codigo_inscripcion' => $inscripcion->codigo_inscripcion,
            'estado' => $inscripcion->estado,
            'estado_label' => $inscripcion->estadoLabel(),
            'fecha_inscripcion' => $inscripcion->fecha_inscripcion?->format('Y-m-d H:i'),
            'gestion' => $inscripcion->gestionCup ? [
                'nombre_gestion' => $inscripcion->gestionCup->nombre_gestion,
                'convocatoria' => $inscripcion->gestionCup->convocatoria,
            ] : null,
            'postulante' => $this->presentarPostulante($inscripcion->postulante),
            'carrera_primera' => $inscripcion->carreraPrimera?->nombre_carrera,
            'carrera_segunda' => $inscripcion->carreraSegunda?->nombre_carrera,
            'pago' => $this->presentarPago($inscripcion->pago),
            'observacion' => $inscripcion->observacion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarDetalle(Inscripcion $inscripcion): array
    {
        return [
            ...$this->presentarResumen($inscripcion),
            'requisitos' => $inscripcion->requisitosCumplidos
                ->map(fn (InscripcionRequisito $cumplimiento): array => [
                    'id_inscripcion_requisito' => $cumplimiento->id_inscripcion_requisito,
                    'nombre_requisito' => $cumplimiento->requisito?->nombre_requisito,
                    'cumplido' => (bool) $cumplimiento->cumplido,
                    'origen' => $cumplimiento->origen,
                    'cumplido_en' => $cumplimiento->cumplido_en?->format('Y-m-d H:i'),
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentarPostulante(?Postulante $postulante): ?array
    {
        if (! $postulante instanceof Postulante) {
            return null;
        }

        return [
            'id_postulante' => $postulante->id_postulante,
            'nombre_completo' => $postulante->nombreCompleto(),
            'ci' => $postulante->ci,
            'correo' => $postulante->correo,
            'telefono' => $postulante->telefono,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'anio_bachillerato' => $postulante->anio_bachillerato,
            'es_extranjero' => (bool) $postulante->es_extranjero,
            'estado' => $postulante->estado,
            'estado_label' => $postulante->estadoLabel(),
            'usuario' => $postulante->usuario ? [
                'id_usuario' => $postulante->usuario->id_usuario,
                'estado' => $postulante->usuario->estado,
                'estado_label' => Usuario::estadosGestionables()[$postulante->usuario->estado] ?? $postulante->usuario->estado,
                'rol' => $postulante->usuario->rol,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentarPago(?PagoInscripcion $pago): ?array
    {
        if (! $pago instanceof PagoInscripcion) {
            return null;
        }

        return [
            'id_pago_inscripcion' => $pago->id_pago_inscripcion,
            'proveedor' => $pago->proveedor,
            'monto_centavos' => $pago->monto_centavos,
            'moneda' => $pago->moneda,
            'monto_label' => strtoupper($pago->moneda).' '.number_format($pago->monto_centavos / 100, 2, '.', ','),
            'estado' => $pago->estado,
            'estado_label' => $pago->estadoLabel(),
            'codigo_comprobante' => $pago->codigo_comprobante,
            'pagado_en' => $pago->pagado_en?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function opcionesGestiones(): Collection
    {
        return GestionCup::query()
            ->orderByDesc('id_gestion')
            ->get()
            ->map(fn (GestionCup $gestionCup): array => [
                'value' => (string) $gestionCup->id_gestion,
                'label' => "{$gestionCup->nombre_gestion} / {$gestionCup->convocatoria}",
            ]);
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function opcionesCarreras(string $gestionId): Collection
    {
        return CarreraCup::query()
            ->when($gestionId !== '', fn (Builder $query) => $query->where('gestion_cup_id', (int) $gestionId))
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->orderBy('nombre_carrera')
            ->get()
            ->map(fn (CarreraCup $carreraCup): array => [
                'value' => (string) $carreraCup->id_carrera_cup,
                'label' => $carreraCup->nombre_carrera,
            ]);
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
