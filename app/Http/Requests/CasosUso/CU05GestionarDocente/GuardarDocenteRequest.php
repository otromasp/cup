<?php

namespace App\Http\Requests\CasosUso\CU05GestionarDocente;

use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'usuario_id' => $this->filled('usuario_id') ? $this->input('usuario_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Docente|null $docente */
        $docente = $this->route('docente');

        return [
            'usuario_id' => [
                'nullable',
                'integer',
                Rule::exists('usuarios', 'id_usuario'),
                Rule::unique('docentes', 'usuario_id')->ignore($docente?->getKey(), 'id_docente'),
            ],
            'ci' => ['required', 'string', 'max:30', Rule::unique('docentes', 'ci')->ignore($docente?->getKey(), 'id_docente')],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:160', Rule::unique('docentes', 'correo')->ignore($docente?->getKey(), 'id_docente')],
            'telefono' => ['nullable', 'string', 'max:40'],
            'profesion' => ['required', 'string', 'max:120'],
            'area_especialidad' => ['required', 'string', 'max:120'],
            'titulo_profesional_afin' => ['required', 'boolean'],
            'tiene_maestria' => ['required', 'boolean'],
            'tiene_diplomado_educacion_superior' => ['required', 'boolean'],
            'maximo_grupos_asignables' => ['required', 'integer', 'min:1', 'max:4'],
            'estado_contratacion' => ['required', Rule::in(array_keys(Docente::estadosContratacion()))],
            'materias' => ['required', 'array', 'min:1'],
            'materias.*' => ['integer', 'distinct', Rule::exists('materias_cup', 'id_materia_cup')->where('estado', Usuario::ESTADO_ACTIVO)],
            'disponibilidades' => ['nullable', 'array'],
            'disponibilidades.*.dia_semana' => ['required_with:disponibilidades', Rule::in(array_keys(DisponibilidadDocente::diasSemana()))],
            'disponibilidades.*.turno' => ['required_with:disponibilidades', Rule::in(array_keys(DisponibilidadDocente::turnos()))],
            'disponibilidades.*.hora_inicio' => ['required_with:disponibilidades', 'date_format:H:i'],
            'disponibilidades.*.hora_fin' => ['required_with:disponibilidades', 'date_format:H:i'],
            'disponibilidades.*.modalidad' => ['required_with:disponibilidades', Rule::in(array_keys(DisponibilidadDocente::modalidades()))],
            'disponibilidades.*.observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarCuentaDocente($validator);
            $this->validarRequisitosHabilitacion($validator);
            $this->validarDisponibilidadNoSolapada($validator);
        });
    }

    /**
     * @return array{
     *     docente: array<string, mixed>,
     *     materias: list<int>,
     *     disponibilidades: list<array<string, mixed>>
     * }
     */
    public function datosDocente(): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->validated();

        return [
            'docente' => [
                'usuario_id' => $datos['usuario_id'] ? (int) $datos['usuario_id'] : null,
                'ci' => trim((string) $datos['ci']),
                'nombres' => trim((string) $datos['nombres']),
                'apellidos' => trim((string) $datos['apellidos']),
                'correo' => mb_strtolower(trim((string) $datos['correo'])),
                'telefono' => filled($datos['telefono'] ?? null) ? trim((string) $datos['telefono']) : null,
                'profesion' => trim((string) $datos['profesion']),
                'area_especialidad' => trim((string) $datos['area_especialidad']),
                'titulo_profesional_afin' => (bool) $datos['titulo_profesional_afin'],
                'tiene_maestria' => (bool) $datos['tiene_maestria'],
                'tiene_diplomado_educacion_superior' => (bool) $datos['tiene_diplomado_educacion_superior'],
                'maximo_grupos_asignables' => (int) $datos['maximo_grupos_asignables'],
                'estado_contratacion' => (string) $datos['estado_contratacion'],
            ],
            'materias' => collect($datos['materias'])->map(fn (mixed $materia): int => (int) $materia)->values()->all(),
            'disponibilidades' => collect($datos['disponibilidades'] ?? [])
                ->map(fn (array $disponibilidad): array => [
                    'dia_semana' => (string) $disponibilidad['dia_semana'],
                    'turno' => (string) $disponibilidad['turno'],
                    'hora_inicio' => (string) $disponibilidad['hora_inicio'],
                    'hora_fin' => (string) $disponibilidad['hora_fin'],
                    'modalidad' => (string) $disponibilidad['modalidad'],
                    'observacion' => filled($disponibilidad['observacion'] ?? null) ? trim((string) $disponibilidad['observacion']) : null,
                ])
                ->values()
                ->all(),
        ];
    }

    private function validarCuentaDocente(Validator $validator): void
    {
        $usuarioId = $this->input('usuario_id');

        if (! $usuarioId) {
            return;
        }

        $usuario = Usuario::query()->find($usuarioId);

        if ($usuario?->rol !== Usuario::ROL_DOCENTE) {
            $validator->errors()->add('usuario_id', 'La cuenta asociada debe tener rol docente.');
        }
    }

    private function validarRequisitosHabilitacion(Validator $validator): void
    {
        if ($this->input('estado_contratacion') !== Docente::ESTADO_HABILITADO) {
            return;
        }

        foreach ([
            'titulo_profesional_afin' => 'Debe confirmar titulo profesional afin para habilitar al docente.',
            'tiene_maestria' => 'Debe confirmar maestria para habilitar al docente.',
            'tiene_diplomado_educacion_superior' => 'Debe confirmar diplomado en educacion superior para habilitar al docente.',
        ] as $campo => $mensaje) {
            if (! filter_var($this->input($campo), FILTER_VALIDATE_BOOLEAN)) {
                $validator->errors()->add($campo, $mensaje);
            }
        }
    }

    private function validarDisponibilidadNoSolapada(Validator $validator): void
    {
        $bloques = collect($this->input('disponibilidades', []))
            ->map(function (array $bloque, int $indice): array {
                return [
                    'indice' => $indice,
                    'dia_semana' => (string) data_get($bloque, 'dia_semana'),
                    'turno' => (string) data_get($bloque, 'turno'),
                    'hora_inicio' => (string) data_get($bloque, 'hora_inicio'),
                    'hora_fin' => (string) data_get($bloque, 'hora_fin'),
                ];
            })
            ->filter(fn (array $bloque): bool => filled($bloque['dia_semana']) && filled($bloque['turno']) && filled($bloque['hora_inicio']) && filled($bloque['hora_fin']))
            ->groupBy(fn (array $bloque): string => "{$bloque['dia_semana']}|{$bloque['turno']}");

        foreach ($bloques as $grupo) {
            $ordenados = $grupo->sortBy('hora_inicio')->values();

            for ($indice = 1; $indice < $ordenados->count(); $indice++) {
                $anterior = $ordenados[$indice - 1];
                $actual = $ordenados[$indice];

                if ($actual['hora_inicio'] < $anterior['hora_fin']) {
                    $validator->errors()->add(
                        "disponibilidades.{$actual['indice']}.hora_inicio",
                        'La disponibilidad no puede solaparse con otro bloque del mismo dia y turno.'
                    );
                }
            }
        }

        foreach ($this->input('disponibilidades', []) as $indice => $bloque) {
            $inicio = (string) data_get($bloque, 'hora_inicio');
            $fin = (string) data_get($bloque, 'hora_fin');

            if (filled($inicio) && filled($fin) && $fin <= $inicio) {
                $validator->errors()->add("disponibilidades.{$indice}.hora_fin", 'La hora de fin debe ser mayor a la hora de inicio.');
            }
        }
    }
}
