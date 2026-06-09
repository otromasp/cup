<?php

namespace App\Http\Requests\CasosUso\CU06RealizarInscripcion;

use App\Models\CarreraCup;
use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'correo' => mb_strtolower(trim((string) $this->input('correo'))),
            'es_extranjero' => filter_var($this->input('es_extranjero'), FILTER_VALIDATE_BOOLEAN),
            'requisitos_cumplidos' => $this->input('requisitos_cumplidos', []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gestion_cup_id' => ['required', 'integer', Rule::exists('gestiones_cup', 'id_gestion')],
            'ci' => ['required', 'string', 'max:30'],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'colegio_procedencia' => ['nullable', 'string', 'max:160'],
            'anio_bachillerato' => ['nullable', 'integer', 'min:1950', 'max:'.((int) now()->year + 1)],
            'es_extranjero' => ['required', 'boolean'],
            'turno_gestion_cup_id' => ['required', 'integer'],
            'carrera_primera_id' => ['required', 'integer'],
            'carrera_segunda_id' => ['nullable', 'integer', 'different:carrera_primera_id'],
            'requisitos_cumplidos' => ['array'],
            'requisitos_cumplidos.*' => ['integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarGestionDisponible($validator);
            $this->validarDuplicidad($validator);
            $this->validarTurno($validator);
            $this->validarCarreras($validator);
            $this->validarRequisitos($validator);
        });
    }

    /**
     * @return array{
     *     gestion_cup_id: int,
     *     postulante: array<string, mixed>,
     *     turno_gestion_cup_id: int,
     *     carrera_primera_id: int,
     *     carrera_segunda_id: int|null,
     *     requisitos_cumplidos: list<int>
     * }
     */
    public function datosInscripcion(): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->validated();

        return [
            'gestion_cup_id' => (int) $datos['gestion_cup_id'],
            'postulante' => [
                'ci' => trim((string) $datos['ci']),
                'nombres' => trim((string) $datos['nombres']),
                'apellidos' => trim((string) $datos['apellidos']),
                'correo' => mb_strtolower(trim((string) $datos['correo'])),
                'telefono' => filled($datos['telefono'] ?? null) ? trim((string) $datos['telefono']) : null,
                'colegio_procedencia' => filled($datos['colegio_procedencia'] ?? null) ? trim((string) $datos['colegio_procedencia']) : null,
                'anio_bachillerato' => filled($datos['anio_bachillerato'] ?? null) ? (int) $datos['anio_bachillerato'] : null,
                'es_extranjero' => (bool) $datos['es_extranjero'],
                'estado' => Postulante::ESTADO_REGISTRADO,
            ],
            'turno_gestion_cup_id' => (int) $datos['turno_gestion_cup_id'],
            'carrera_primera_id' => (int) $datos['carrera_primera_id'],
            'carrera_segunda_id' => filled($datos['carrera_segunda_id'] ?? null) ? (int) $datos['carrera_segunda_id'] : null,
            'requisitos_cumplidos' => collect($datos['requisitos_cumplidos'] ?? [])->map(fn (mixed $id): int => (int) $id)->values()->all(),
        ];
    }

    private function validarGestionDisponible(Validator $validator): void
    {
        $gestion = $this->gestionCup();

        if (! $gestion instanceof GestionCup) {
            return;
        }

        if (! $this->inscripcionAbierta($gestion)) {
            $validator->errors()->add('gestion_cup_id', 'La gestion CUP todavia no esta habilitada para inscripcion.');
        }
    }

    private function validarDuplicidad(Validator $validator): void
    {
        $ci = trim((string) $this->input('ci'));
        $correo = mb_strtolower(trim((string) $this->input('correo')));

        if (Usuario::query()->where('ci', $ci)->orWhere('correo', $correo)->exists()) {
            $validator->errors()->add('ci', 'Ya existe una cuenta de usuario registrada con el CI o correo ingresado.');

            return;
        }

        $postulantePorCi = Postulante::query()->where('ci', $ci)->first();
        $postulantePorCorreo = Postulante::query()->where('correo', $correo)->first();

        if ($postulantePorCi instanceof Postulante
            && $postulantePorCorreo instanceof Postulante
            && $postulantePorCi->id_postulante !== $postulantePorCorreo->id_postulante) {
            $validator->errors()->add('ci', 'El CI y correo pertenecen a postulantes distintos.');

            return;
        }

        if (! $postulantePorCi instanceof Postulante && $postulantePorCorreo instanceof Postulante) {
            $validator->errors()->add('correo', 'El correo pertenece a otro postulante registrado.');

            return;
        }

        $postulante = $postulantePorCi ?? $postulantePorCorreo;

        if (! $postulante instanceof Postulante) {
            return;
        }

        $inscripcionConfirmada = Inscripcion::query()
            ->where('gestion_cup_id', (int) $this->input('gestion_cup_id'))
            ->where('postulante_id', $postulante->id_postulante)
            ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
            ->exists();

        if ($inscripcionConfirmada) {
            $validator->errors()->add('ci', 'El postulante ya tiene una inscripcion confirmada en esta gestion CUP.');
        }
    }

    private function validarCarreras(Validator $validator): void
    {
        $gestionId = (int) $this->input('gestion_cup_id');
        $carreras = collect([$this->input('carrera_primera_id'), $this->input('carrera_segunda_id')])
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        if ($carreras->isEmpty()) {
            return;
        }

        $carrerasValidas = CarreraCup::query()
            ->where('gestion_cup_id', $gestionId)
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->whereIn('id_carrera_cup', $carreras)
            ->pluck('id_carrera_cup');

        if ($carrerasValidas->count() !== $carreras->unique()->count()) {
            $validator->errors()->add('carrera_primera_id', 'Debe seleccionar carreras activas de la gestion CUP.');
        }
    }

    private function validarTurno(Validator $validator): void
    {
        $gestionId = (int) $this->input('gestion_cup_id');
        $turnoId = (int) $this->input('turno_gestion_cup_id');

        if ($turnoId <= 0) {
            return;
        }

        $turno = TurnoGestionCup::query()
            ->where('gestion_cup_id', $gestionId)
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->find($turnoId);

        if (! $turno instanceof TurnoGestionCup) {
            $validator->errors()->add('turno_gestion_cup_id', 'Debe seleccionar un turno disponible de la gestion CUP.');

            return;
        }

        $ocupados = Inscripcion::query()
            ->where('gestion_cup_id', $gestionId)
            ->where('turno_gestion_cup_id', $turno->id_turno_gestion)
            ->where('estado', Inscripcion::ESTADO_CONFIRMADA)
            ->count();

        if ($ocupados >= $turno->capacidad_maxima) {
            $validator->errors()->add('turno_gestion_cup_id', 'El turno seleccionado ya no tiene cupos disponibles.');
        }
    }

    private function validarRequisitos(Validator $validator): void
    {
        $gestionId = (int) $this->input('gestion_cup_id');
        $seleccionados = collect($this->input('requisitos_cumplidos', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        $requisitos = RequisitoInscripcion::query()
            ->where('gestion_cup_id', $gestionId)
            ->where('estado', Usuario::ESTADO_ACTIVO)
            ->get();

        foreach ($requisitos as $requisito) {
            if (! $this->requisitoAplica($requisito) || $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO) {
                continue;
            }

            if ($requisito->obligatorio && ! $seleccionados->contains($requisito->id_requisito)) {
                $validator->errors()->add('requisitos_cumplidos', "Debe confirmar el requisito: {$requisito->nombre_requisito}.");
            }
        }
    }

    private function requisitoAplica(RequisitoInscripcion $requisito): bool
    {
        return $requisito->aplica_a === RequisitoInscripcion::APLICA_TODOS
            || ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && $this->boolean('es_extranjero'));
    }

    private function gestionCup(): ?GestionCup
    {
        return GestionCup::query()->with('etapas')->find($this->input('gestion_cup_id'));
    }

    private function inscripcionAbierta(GestionCup $gestionCup): bool
    {
        if (! in_array($gestionCup->estado_configuracion, [GestionCup::ESTADO_CONFIGURADA, GestionCup::ESTADO_BLOQUEADA], true)) {
            return false;
        }

        if ($gestionCup->etapas->isEmpty()) {
            return $gestionCup->estado_configuracion === GestionCup::ESTADO_CONFIGURADA;
        }

        return $gestionCup->etapas
            ->contains(fn (EtapaGestionCup $etapa): bool => $etapa->estado_etapa === EtapaGestionCup::ESTADO_ACTIVA
                && str_contains(mb_strtolower($etapa->nombre_etapa), 'inscrip'));
    }
}
