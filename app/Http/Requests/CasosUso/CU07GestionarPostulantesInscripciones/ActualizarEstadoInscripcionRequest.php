<?php

namespace App\Http\Requests\CasosUso\CU07GestionarPostulantesInscripciones;

use App\Models\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarEstadoInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'observacion' => filled($this->input('observacion')) ? trim((string) $this->input('observacion')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(array_keys(Inscripcion::estadosGestionables()))],
            'observacion' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Debe seleccionar el estado de la inscripcion.',
            'estado.in' => 'Debe seleccionar un estado de inscripcion valido.',
            'observacion.max' => 'La observacion no debe superar 500 caracteres.',
        ];
    }

    /**
     * @return array{estado: string, observacion: ?string}
     */
    public function datosEstado(): array
    {
        /** @var array{estado: string, observacion?: ?string} $datos */
        $datos = $this->validated();

        return [
            'estado' => $datos['estado'],
            'observacion' => $datos['observacion'] ?? null,
        ];
    }
}
