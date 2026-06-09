<?php

namespace App\Http\Controllers\CasosUso\CU03ConfigurarGestionCUP;

use App\Actions\CasosUso\CU03ConfigurarGestionCUP\GuardarGestionCupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU03ConfigurarGestionCUP\GuardarGestionCupRequest;
use App\Models\GestionCup;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ControladorGestionCUP extends Controller
{
    public function index(Request $request): Response
    {
        $gestiones = GestionCup::query()
            ->with('usuarioResponsable:id_usuario,nombre,rol')
            ->withCount(['turnos', 'carreras', 'materias', 'requisitos'])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn (GestionCup $gestionCup): array => $this->presentarResumen($gestionCup));

        return Inertia::render('casos-uso/cu03-configurar-gestion-cup/index', [
            'gestiones' => $gestiones,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('casos-uso/cu03-configurar-gestion-cup/create', [
            'gestion' => $this->configuracionPredeterminada(),
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(GuardarGestionCupRequest $request, GuardarGestionCupAction $guardarGestionCup): RedirectResponse
    {
        /** @var Usuario $actor */
        $actor = $request->user();

        $gestionCup = $guardarGestionCup->execute($request->datosGestion(), $actor);

        return to_route('cu03.gestion-cup.edit', $gestionCup)
            ->with('status', 'Gestion CUP configurada correctamente.');
    }

    public function edit(Request $request, GestionCup $gestionCup): Response
    {
        $gestionCup->load(['turnos', 'carreras', 'materias', 'requisitos', 'usuarioResponsable']);

        return Inertia::render('casos-uso/cu03-configurar-gestion-cup/edit', [
            'gestion' => $this->presentarFormulario($gestionCup),
            'opciones' => $this->opcionesFormulario(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(
        GuardarGestionCupRequest $request,
        GestionCup $gestionCup,
        GuardarGestionCupAction $guardarGestionCup
    ): RedirectResponse {
        /** @var Usuario $actor */
        $actor = $request->user();

        $guardarGestionCup->execute($request->datosGestion(), $actor, $gestionCup);

        return back()->with('status', 'Gestion CUP actualizada correctamente.');
    }

    /**
     * @return array<string, Collection<int, array{value: string, label: string}>>
     */
    private function opcionesFormulario(): array
    {
        return [
            'estados' => $this->mapearOpciones(Usuario::estadosGestionables()),
            'estadosConfiguracion' => $this->mapearOpciones(GestionCup::estadosConfiguracion()),
            'monedasInscripcion' => $this->mapearOpciones(GestionCup::monedasInscripcion()),
            'turnos' => $this->mapearOpciones(TurnoGestionCup::turnos()),
            'modalidades' => $this->mapearOpciones(TurnoGestionCup::modalidades()),
            'tiposRequisito' => $this->mapearOpciones(RequisitoInscripcion::tiposRequisito()),
            'ambitosAplicacion' => $this->mapearOpciones(RequisitoInscripcion::ambitosAplicacion()),
        ];
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
    private function configuracionPredeterminada(): array
    {
        return [
            'id_gestion' => null,
            'nombre_gestion' => 'Cursos CUP Preuniversitarios',
            'convocatoria' => '2-'.now()->year,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'nota_minima_aprobacion' => '60.00',
            'costo_inscripcion' => '1000.00',
            'moneda_inscripcion' => GestionCup::MONEDA_BOB,
            'estado_configuracion' => GestionCup::ESTADO_EN_CONFIGURACION,
            'responsable' => null,
            'turnos' => [
                $this->turnoPredeterminado(TurnoGestionCup::TURNO_MANANA, 1, 140),
                $this->turnoPredeterminado(TurnoGestionCup::TURNO_TARDE, 2, 140),
                $this->turnoPredeterminado(TurnoGestionCup::TURNO_NOCHE, 3, 70),
            ],
            'carreras' => [
                ['id_carrera_cup' => null, 'nombre_carrera' => 'Ingenieria de Sistemas', 'cupo_disponible' => '120', 'estado' => Usuario::ESTADO_ACTIVO],
                ['id_carrera_cup' => null, 'nombre_carrera' => 'Ingenieria Informatica', 'cupo_disponible' => '100', 'estado' => Usuario::ESTADO_ACTIVO],
                ['id_carrera_cup' => null, 'nombre_carrera' => 'Redes y Telecomunicaciones', 'cupo_disponible' => '70', 'estado' => Usuario::ESTADO_ACTIVO],
                ['id_carrera_cup' => null, 'nombre_carrera' => 'Robotica', 'cupo_disponible' => '60', 'estado' => Usuario::ESTADO_ACTIVO],
            ],
            'materias' => [
                $this->materiaPredeterminada('Computacion'),
                $this->materiaPredeterminada('Matematica'),
                $this->materiaPredeterminada('Ingles'),
                $this->materiaPredeterminada('Fisica'),
            ],
            'requisitos' => [
                $this->requisitoPredeterminado('Original y fotocopia del titulo de bachiller'),
                $this->requisitoPredeterminado('Fotocopia de carnet de identidad'),
                $this->requisitoPredeterminado('Formulario de preinscripcion'),
                $this->requisitoPredeterminado('Libreta o certificado de ultimo ano de secundaria'),
                $this->requisitoPredeterminado('Comprobante de pago', RequisitoInscripcion::TIPO_PAGO),
                $this->requisitoPredeterminado(
                    'Certificado de radicatoria emitido por Migracion',
                    RequisitoInscripcion::TIPO_DECLARATIVO,
                    RequisitoInscripcion::APLICA_EXTRANJEROS
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function turnoPredeterminado(string $turno, int $orden, int $capacidad): array
    {
        return [
            'id_turno_gestion' => null,
            'turno' => $turno,
            'orden' => (string) $orden,
            'capacidad_maxima' => (string) $capacidad,
            'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function materiaPredeterminada(string $nombreMateria): array
    {
        return [
            'id_materia_cup' => null,
            'nombre_materia' => $nombreMateria,
            'ponderacion_nota1' => '30.00',
            'ponderacion_nota2' => '30.00',
            'ponderacion_nota3' => '40.00',
            'nota_minima' => '60.00',
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requisitoPredeterminado(
        string $nombreRequisito,
        string $tipoRequisito = RequisitoInscripcion::TIPO_DECLARATIVO,
        string $aplicaA = RequisitoInscripcion::APLICA_TODOS
    ): array {
        return [
            'id_requisito' => null,
            'nombre_requisito' => $nombreRequisito,
            'obligatorio' => true,
            'tipo_requisito' => $tipoRequisito,
            'aplica_a' => $aplicaA,
            'estado' => Usuario::ESTADO_ACTIVO,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarFormulario(GestionCup $gestionCup): array
    {
        return [
            'id_gestion' => $gestionCup->id_gestion,
            'nombre_gestion' => $gestionCup->nombre_gestion,
            'convocatoria' => $gestionCup->convocatoria,
            'fecha_inicio' => $gestionCup->fecha_inicio?->toDateString(),
            'fecha_fin' => $gestionCup->fecha_fin?->toDateString(),
            'nota_minima_aprobacion' => $this->decimal($gestionCup->nota_minima_aprobacion),
            'costo_inscripcion' => $this->decimal($gestionCup->costo_inscripcion),
            'moneda_inscripcion' => $gestionCup->moneda_inscripcion,
            'estado_configuracion' => $gestionCup->estado_configuracion,
            'responsable' => $gestionCup->usuarioResponsable?->nombre,
            'turnos' => $gestionCup->turnos
                ->map(fn (TurnoGestionCup $turnoGestionCup): array => [
                    'id_turno_gestion' => $turnoGestionCup->id_turno_gestion,
                    'turno' => $turnoGestionCup->turno,
                    'orden' => (string) $turnoGestionCup->orden,
                    'capacidad_maxima' => (string) $turnoGestionCup->capacidad_maxima,
                    'modalidad' => $turnoGestionCup->modalidad,
                    'estado' => $turnoGestionCup->estado,
                ])
                ->values(),
            'carreras' => $gestionCup->carreras
                ->map(fn ($carrera): array => [
                    'id_carrera_cup' => $carrera->id_carrera_cup,
                    'nombre_carrera' => $carrera->nombre_carrera,
                    'cupo_disponible' => (string) $carrera->cupo_disponible,
                    'estado' => $carrera->estado,
                ])
                ->values(),
            'materias' => $gestionCup->materias
                ->map(fn ($materia): array => [
                    'id_materia_cup' => $materia->id_materia_cup,
                    'nombre_materia' => $materia->nombre_materia,
                    'ponderacion_nota1' => $this->decimal($materia->ponderacion_nota1),
                    'ponderacion_nota2' => $this->decimal($materia->ponderacion_nota2),
                    'ponderacion_nota3' => $this->decimal($materia->ponderacion_nota3),
                    'nota_minima' => $this->decimal($materia->nota_minima),
                    'estado' => $materia->estado,
                ])
                ->values(),
            'requisitos' => $gestionCup->requisitos
                ->map(fn ($requisito): array => [
                    'id_requisito' => $requisito->id_requisito,
                    'nombre_requisito' => $requisito->nombre_requisito,
                    'obligatorio' => (bool) $requisito->obligatorio,
                    'tipo_requisito' => $requisito->tipo_requisito,
                    'aplica_a' => $requisito->aplica_a,
                    'estado' => $requisito->estado,
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentarResumen(GestionCup $gestionCup): array
    {
        return [
            'id_gestion' => $gestionCup->id_gestion,
            'nombre_gestion' => $gestionCup->nombre_gestion,
            'convocatoria' => $gestionCup->convocatoria,
            'fecha_inicio' => $gestionCup->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $gestionCup->fecha_fin?->format('Y-m-d'),
            'nota_minima_aprobacion' => $this->decimal($gestionCup->nota_minima_aprobacion),
            'costo_inscripcion' => $gestionCup->costoInscripcionLabel(),
            'estado_configuracion' => $gestionCup->estado_configuracion,
            'estado_configuracion_label' => GestionCup::estadosConfiguracion()[$gestionCup->estado_configuracion] ?? $gestionCup->estado_configuracion,
            'responsable' => $gestionCup->usuarioResponsable?->nombre,
            'turnos_count' => $gestionCup->turnos_count,
            'carreras_count' => $gestionCup->carreras_count,
            'materias_count' => $gestionCup->materias_count,
            'requisitos_count' => $gestionCup->requisitos_count,
        ];
    }

    private function decimal(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
