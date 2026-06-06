<?php

namespace App\Http\Requests\CasosUso\CU01GestionarAcceso;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RestablecerContrasenaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function correo(): string
    {
        return Str::lower((string) $this->string('email'));
    }

    public function tokenRecuperacion(): string
    {
        return (string) $this->string('token');
    }

    public function nuevaContrasena(): string
    {
        return (string) $this->string('password');
    }
}
