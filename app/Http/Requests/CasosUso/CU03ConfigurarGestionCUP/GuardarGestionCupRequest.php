<?php

namespace App\Http\Requests\CasosUso\CU03ConfigurarGestionCUP;

use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarGestionCupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var GestionCup|null $gestionCup */
        $gestionCup = $this->route('gestionCup');

        return [
            'nombre_gestion' => [
                'required',
                'string',
                'max:120',
                Rule::unique('gestiones_cup', 'nombre_gestion')
                    ->where('convocatoria', $this->input('convocatoria'))
                    ->ignore($gestionCup?->getKey(), 'id_gestion'),
            ],
            'convocatoria' => ['required', 'string', 'max:120'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'nota_minima_aprobacion' => ['required', 'numeric', 'min:0', 'max:100'],
            'costo_inscripcion' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'moneda_inscripcion' => ['required', Rule::in(array_keys(GestionCup::monedasInscripcion()))],
            'estado_configuracion' => ['required', Rule::in(array_keys(GestionCup::estadosConfiguracion()))],

            'turnos' => ['required', 'array', 'min:1'],
            'turnos.*.id_turno_gestion' => ['nullable', 'integer'],
            'turnos.*.turno' => ['required', Rule::in(array_keys(TurnoGestionCup::turnos()))],
            'turnos.*.orden' => ['nullable', 'integer', 'min:1', 'max:99'],
            'turnos.*.capacidad_maxima' => ['required', 'integer', 'min:1', 'max:9999'],
            'turnos.*.modalidad' => ['required', Rule::in(array_keys(TurnoGestionCup::modalidades()))],
            'turnos.*.estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],

            'carreras' => ['required', 'array', 'min:1'],
            'carreras.*.id_carrera_cup' => ['nullable', 'integer'],
            'carreras.*.nombre_carrera' => ['required', 'string', 'max:120'],
            'carreras.*.cupo_disponible' => ['required', 'integer', 'min:1', 'max:9999'],
            'carreras.*.estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],

            'materias' => ['required', 'array', 'min:1'],
            'materias.*.id_materia_cup' => ['nullable', 'integer'],
            'materias.*.nombre_materia' => ['required', 'string', 'max:120'],
            'materias.*.ponderacion_nota1' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias.*.ponderacion_nota2' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias.*.ponderacion_nota3' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias.*.nota_minima' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias.*.estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],

            'requisitos' => ['required', 'array', 'min:1'],
            'requisitos.*.id_requisito' => ['nullable', 'integer'],
            'requisitos.*.nombre_requisito' => ['required', 'string', 'max:160'],
            'requisitos.*.obligatorio' => ['required', 'boolean'],
            'requisitos.*.tipo_requisito' => ['required', Rule::in(array_keys(RequisitoInscripcion::tiposRequisito()))],
            'requisitos.*.aplica_a' => ['required', Rule::in(array_keys(RequisitoInscripcion::ambitosAplicacion()))],
            'requisitos.*.estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarDuplicados($validator, 'carreras', 'nombre_carrera', 'No se puede repetir una carrera en la misma gestion.');
            $this->validarDuplicados($validator, 'materias', 'nombre_materia', 'No se puede repetir una materia en la misma gestion.');
            $this->validarDuplicados($validator, 'requisitos', 'nombre_requisito', 'No se puede repetir un requisito en la misma gestion.');
            $this->validarDuplicados($validator, 'turnos', 'turno', 'No se puede repetir un turno en la misma gestion.');
            $this->validarPonderaciones($validator);
            $this->validarCostoDePago($validator);
            $this->validarIdsDeLaGestion($validator);
            $this->validarCapacidadesEnUso($validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'fecha_inicio.required' => 'Debe indicar la fecha de inicio.',
            'fecha_fin.required' => 'Debe indicar la fecha de fin.',
            'fecha_inicio.date' => 'La fecha de inicio no es valida.',
            'fecha_fin.date' => 'La fecha de fin no es valida.',
            'costo_inscripcion.required' => 'Debe indicar el costo de inscripcion.',
            'costo_inscripcion.numeric' => 'El costo de inscripcion debe ser un numero.',
            'moneda_inscripcion.required' => 'Debe seleccionar la moneda de inscripcion.',
            'moneda_inscripcion.in' => 'Debe seleccionar una moneda de inscripcion valida.',
            'nota_minima_aprobacion.required' => 'Debe indicar la nota minima de aprobacion.',
            'nota_minima_aprobacion.numeric' => 'La nota minima de aprobacion debe ser un numero.',
            'estado_configuracion.required' => 'Debe seleccionar el estado de la gestion.',
            'estado_configuracion.in' => 'Debe seleccionar un estado de gestion valido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'costo_inscripcion' => 'costo de inscripcion',
            'moneda_inscripcion' => 'moneda de inscripcion',
            'nota_minima_aprobacion' => 'nota minima de aprobacion',
            'estado_configuracion' => 'estado de la gestion',
        ];
    }

    /**
     * @return array{
     *     gestion: array{nombre_gestion: string, convocatoria: string, fecha_inicio: string, fecha_fin: string, nota_minima_aprobacion: float, costo_inscripcion: float, moneda_inscripcion: string, estado_configuracion: string},
     *     turnos: list<array{id_turno_gestion: int|null, turno: string, orden: int, capacidad_maxima: int, modalidad: string, estado: string}>,
     *     carreras: list<array{id_carrera_cup: int|null, nombre_carrera: string, cupo_disponible: int, estado: string}>,
     *     materias: list<array{id_materia_cup: int|null, nombre_materia: string, ponderacion_nota1: float, ponderacion_nota2: float, ponderacion_nota3: float, nota_minima: float, estado: string}>,
     *     requisitos: list<array{id_requisito: int|null, nombre_requisito: string, obligatorio: bool, tipo_requisito: string, aplica_a: string, estado: string}>
     * }
     */
    public function datosGestion(): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->validated();

        return [
            'gestion' => [
                'nombre_gestion' => trim((string) $datos['nombre_gestion']),
                'convocatoria' => trim((string) $datos['convocatoria']),
                'fecha_inicio' => (string) $datos['fecha_inicio'],
                'fecha_fin' => (string) $datos['fecha_fin'],
                'nota_minima_aprobacion' => (float) $datos['nota_minima_aprobacion'],
                'costo_inscripcion' => (float) $datos['costo_inscripcion'],
                'moneda_inscripcion' => mb_strtolower((string) $datos['moneda_inscripcion']),
                'estado_configuracion' => (string) $datos['estado_configuracion'],
            ],
            'turnos' => collect($datos['turnos'])
                ->sortBy(fn (array $turno): int => (int) ($turno['orden'] ?? 999))
                ->values()
                ->map(fn (array $turno, int $indice): array => [
                    'id_turno_gestion' => filled($turno['id_turno_gestion'] ?? null) ? (int) $turno['id_turno_gestion'] : null,
                    'turno' => (string) $turno['turno'],
                    'orden' => $indice + 1,
                    'capacidad_maxima' => (int) $turno['capacidad_maxima'],
                    'modalidad' => (string) $turno['modalidad'],
                    'estado' => (string) $turno['estado'],
                ])
                ->all(),
            'carreras' => collect($datos['carreras'])
                ->map(fn (array $carrera): array => [
                    'id_carrera_cup' => filled($carrera['id_carrera_cup'] ?? null) ? (int) $carrera['id_carrera_cup'] : null,
                    'nombre_carrera' => trim((string) $carrera['nombre_carrera']),
                    'cupo_disponible' => (int) $carrera['cupo_disponible'],
                    'estado' => (string) $carrera['estado'],
                ])
                ->values()
                ->all(),
            'materias' => collect($datos['materias'])
                ->map(fn (array $materia): array => [
                    'id_materia_cup' => filled($materia['id_materia_cup'] ?? null) ? (int) $materia['id_materia_cup'] : null,
                    'nombre_materia' => trim((string) $materia['nombre_materia']),
                    'ponderacion_nota1' => (float) $materia['ponderacion_nota1'],
                    'ponderacion_nota2' => (float) $materia['ponderacion_nota2'],
                    'ponderacion_nota3' => (float) $materia['ponderacion_nota3'],
                    'nota_minima' => (float) $materia['nota_minima'],
                    'estado' => (string) $materia['estado'],
                ])
                ->values()
                ->all(),
            'requisitos' => collect($datos['requisitos'])
                ->map(fn (array $requisito): array => [
                    'id_requisito' => filled($requisito['id_requisito'] ?? null) ? (int) $requisito['id_requisito'] : null,
                    'nombre_requisito' => trim((string) $requisito['nombre_requisito']),
                    'obligatorio' => (bool) $requisito['obligatorio'],
                    'tipo_requisito' => (string) $requisito['tipo_requisito'],
                    'aplica_a' => (string) $requisito['aplica_a'],
                    'estado' => (string) $requisito['estado'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function validarDuplicados(Validator $validator, string $coleccion, string $campo, string $mensaje): void
    {
        $valores = collect($this->input($coleccion, []))
            ->map(fn (mixed $item): string => mb_strtolower(trim((string) data_get($item, $campo))))
            ->filter();

        if ($valores->duplicates()->isNotEmpty()) {
            $validator->errors()->add($coleccion, $mensaje);
        }
    }

    private function validarPonderaciones(Validator $validator): void
    {
        foreach ($this->input('materias', []) as $indice => $materia) {
            $suma = (float) data_get($materia, 'ponderacion_nota1', 0)
                + (float) data_get($materia, 'ponderacion_nota2', 0)
                + (float) data_get($materia, 'ponderacion_nota3', 0);

            if (abs($suma - 100) > 0.01) {
                $validator->errors()->add("materias.{$indice}.ponderacion_nota1", 'Las tres ponderaciones de la materia deben sumar 100%.');
            }
        }
    }

    private function validarCostoDePago(Validator $validator): void
    {
        $tienePagoActivo = collect($this->input('requisitos', []))
            ->contains(fn (mixed $requisito): bool => data_get($requisito, 'tipo_requisito') === RequisitoInscripcion::TIPO_PAGO
                && data_get($requisito, 'estado') === Usuario::ESTADO_ACTIVO
                && filter_var(data_get($requisito, 'obligatorio'), FILTER_VALIDATE_BOOLEAN));

        if ($tienePagoActivo && (float) $this->input('costo_inscripcion', 0) <= 0) {
            $validator->errors()->add('costo_inscripcion', 'Debe configurar un costo de inscripcion mayor a cero si existe requisito de pago.');
        }
    }

    private function validarIdsDeLaGestion(Validator $validator): void
    {
        /** @var GestionCup|null $gestionCup */
        $gestionCup = $this->route('gestionCup');

        if (! $gestionCup instanceof GestionCup) {
            return;
        }

        $this->validarIds($validator, 'turnos', 'id_turno_gestion', $gestionCup->turnos()->pluck('id_turno_gestion')->all(), 'El turno seleccionado no pertenece a la gestion CUP.');
        $this->validarIds($validator, 'carreras', 'id_carrera_cup', $gestionCup->carreras()->pluck('id_carrera_cup')->all(), 'La carrera seleccionada no pertenece a la gestion CUP.');
        $this->validarIds($validator, 'materias', 'id_materia_cup', $gestionCup->materias()->pluck('id_materia_cup')->all(), 'La materia seleccionada no pertenece a la gestion CUP.');
        $this->validarIds($validator, 'requisitos', 'id_requisito', $gestionCup->requisitos()->pluck('id_requisito')->all(), 'El requisito seleccionado no pertenece a la gestion CUP.');
    }

    /**
     * @param  list<int>  $idsPermitidos
     */
    private function validarIds(Validator $validator, string $coleccion, string $campo, array $idsPermitidos, string $mensaje): void
    {
        $permitidos = collect($idsPermitidos)->map(fn (int|string $id): int => (int) $id);

        foreach ($this->input($coleccion, []) as $indice => $item) {
            $id = data_get($item, $campo);

            if (filled($id) && ! $permitidos->contains((int) $id)) {
                $validator->errors()->add("{$coleccion}.{$indice}.{$campo}", $mensaje);
            }
        }
    }

    private function validarCapacidadesEnUso(Validator $validator): void
    {
        /** @var GestionCup|null $gestionCup */
        $gestionCup = $this->route('gestionCup');

        if (! $gestionCup instanceof GestionCup) {
            return;
        }

        foreach ($this->input('turnos', []) as $indice => $turno) {
            $id = data_get($turno, 'id_turno_gestion');

            if (blank($id)) {
                continue;
            }

            $confirmadas = Inscripcion::query()
                ->where('gestion_cup_id', $gestionCup->id_gestion)
                ->where('turno_gestion_cup_id', (int) $id)
                ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
                ->count();

            if ((int) data_get($turno, 'capacidad_maxima', 0) < $confirmadas) {
                $validator->errors()->add("turnos.{$indice}.capacidad_maxima", "El turno ya tiene {$confirmadas} inscripciones confirmadas.");
            }
        }

        foreach ($this->input('carreras', []) as $indice => $carrera) {
            $id = data_get($carrera, 'id_carrera_cup');

            if (blank($id)) {
                continue;
            }

            $confirmadas = Inscripcion::query()
                ->where('gestion_cup_id', $gestionCup->id_gestion)
                ->where('carrera_primera_id', (int) $id)
                ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
                ->count();

            if ((int) data_get($carrera, 'cupo_disponible', 0) < $confirmadas) {
                $validator->errors()->add("carreras.{$indice}.cupo_disponible", "La carrera ya tiene {$confirmadas} inscripciones confirmadas como primera opcion.");
            }
        }
    }
}
