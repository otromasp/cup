<?php

namespace App\Http\Requests\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarUsuarioRequest extends FormRequest
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
        /** @var Usuario|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'ci' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('usuarios', 'ci')->ignore($usuario?->getKey(), 'id_usuario'),
            ],
            'correo' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($usuario?->getKey(), 'id_usuario'),
            ],
            'rol' => ['required', Rule::in(array_keys(Usuario::rolesGestionables()))],
            'estado' => ['required', Rule::in(array_keys(Usuario::estadosGestionables()))],
        ];
    }

    /**
     * @return array{nombre: string, ci: ?string, correo: string, rol: string, estado: string}
     */
    public function datosUsuario(): array
    {
        return [
            'nombre' => (string) $this->string('nombre'),
            'ci' => $this->filled('ci') ? (string) $this->string('ci') : null,
            'correo' => (string) $this->string('correo')->lower(),
            'rol' => (string) $this->string('rol'),
            'estado' => (string) $this->string('estado'),
        ];
    }
}
