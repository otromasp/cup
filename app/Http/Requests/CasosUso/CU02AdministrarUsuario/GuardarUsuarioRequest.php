<?php

namespace App\Http\Requests\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class GuardarUsuarioRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:120'],
            'ci' => ['nullable', 'string', 'max:30', Rule::unique('usuarios', 'ci')],
            'correo' => ['required', 'string', 'email', 'max:150', Rule::unique('usuarios', 'correo')],
            'rol' => ['required', Rule::in(array_keys(Usuario::rolesGestionables()))],
            'estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * @return array{nombre: string, ci: ?string, correo: string, rol: string, estado: string, contrasena_hash: ?string}
     */
    public function datosUsuario(): array
    {
        return [
            'nombre' => (string) $this->string('nombre'),
            'ci' => $this->filled('ci') ? (string) $this->string('ci') : null,
            'correo' => (string) $this->string('correo')->lower(),
            'rol' => (string) $this->string('rol'),
            'estado' => (string) $this->string('estado'),
            'contrasena_hash' => $this->filled('password') ? (string) $this->string('password') : null,
        ];
    }
}
