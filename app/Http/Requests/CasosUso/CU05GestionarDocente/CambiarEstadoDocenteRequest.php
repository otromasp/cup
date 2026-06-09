<?php

namespace App\Http\Requests\CasosUso\CU05GestionarDocente;

use App\Models\Docente;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado_contratacion' => ['required', Rule::in(array_keys(Docente::estadosContratacion()))],
        ];
    }
}
