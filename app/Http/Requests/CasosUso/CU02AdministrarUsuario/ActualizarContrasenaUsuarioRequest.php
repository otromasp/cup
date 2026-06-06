<?php

namespace App\Http\Requests\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class ActualizarContrasenaUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->esAdministrador();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function nuevaContrasena(): string
    {
        return (string) $this->string('password');
    }
}
