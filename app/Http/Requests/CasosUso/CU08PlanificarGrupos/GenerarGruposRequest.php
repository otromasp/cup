<?php

namespace App\Http\Requests\CasosUso\CU08PlanificarGrupos;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerarGruposRequest extends FormRequest
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
            'gestion_cup_id' => ['required', 'integer', Rule::exists('gestiones_cup', 'id_gestion')],
        ];
    }

    public function gestionCupId(): int
    {
        return (int) $this->validated('gestion_cup_id');
    }
}
