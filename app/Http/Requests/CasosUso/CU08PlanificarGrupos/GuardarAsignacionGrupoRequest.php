<?php

namespace App\Http\Requests\CasosUso\CU08PlanificarGrupos;

use App\Models\DisponibilidadDocente;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarAsignacionGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aula' => filled($this->input('aula')) ? trim((string) $this->input('aula')) : null,
            'enlace_clase' => filled($this->input('enlace_clase')) ? trim((string) $this->input('enlace_clase')) : null,
            'observacion' => filled($this->input('observacion')) ? trim((string) $this->input('observacion')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grupo_cup_id' => ['required', 'integer', Rule::exists('grupos_cup', 'id_grupo_cup')],
            'materia_cup_id' => ['required', 'integer', Rule::exists('materias_cup', 'id_materia_cup')],
            'docente_id' => ['required', 'integer', Rule::exists('docentes', 'id_docente')],
            'dia_semana' => ['required', Rule::in(array_keys(DisponibilidadDocente::diasSemana()))],
            'turno' => ['required', Rule::in(array_keys(DisponibilidadDocente::turnos()))],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'modalidad' => ['required', Rule::in(array_keys(DisponibilidadDocente::modalidades()))],
            'aula' => ['nullable', 'string', 'max:80'],
            'enlace_clase' => ['nullable', 'url', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inicio = (string) $this->input('hora_inicio');
            $fin = (string) $this->input('hora_fin');

            if (filled($inicio) && filled($fin) && $fin <= $inicio) {
                $validator->errors()->add('hora_fin', 'La hora de fin debe ser mayor a la hora de inicio.');
            }
        });
    }

    /**
     * @return array{
     *     grupo_cup_id: int,
     *     materia_cup_id: int,
     *     docente_id: int,
     *     dia_semana: string,
     *     turno: string,
     *     hora_inicio: string,
     *     hora_fin: string,
     *     modalidad: string,
     *     aula: ?string,
     *     enlace_clase: ?string,
     *     observacion: ?string
     * }
     */
    public function datosAsignacion(): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->validated();

        return [
            'grupo_cup_id' => (int) $datos['grupo_cup_id'],
            'materia_cup_id' => (int) $datos['materia_cup_id'],
            'docente_id' => (int) $datos['docente_id'],
            'dia_semana' => (string) $datos['dia_semana'],
            'turno' => (string) $datos['turno'],
            'hora_inicio' => (string) $datos['hora_inicio'],
            'hora_fin' => (string) $datos['hora_fin'],
            'modalidad' => (string) $datos['modalidad'],
            'aula' => $datos['aula'] ?? null,
            'enlace_clase' => $datos['enlace_clase'] ?? null,
            'observacion' => $datos['observacion'] ?? null,
        ];
    }
}
