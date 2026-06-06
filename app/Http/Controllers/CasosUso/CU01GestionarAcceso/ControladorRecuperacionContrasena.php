<?php

namespace App\Http\Controllers\CasosUso\CU01GestionarAcceso;

use App\Actions\CasosUso\CU01GestionarAcceso\RestablecerContrasenaAction;
use App\Actions\CasosUso\CU01GestionarAcceso\SolicitarRecuperacionContrasenaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU01GestionarAcceso\RestablecerContrasenaRequest;
use App\Http\Requests\CasosUso\CU01GestionarAcceso\SolicitarRecuperacionContrasenaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ControladorRecuperacionContrasena extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('casos-uso/cu01-gestionar-acceso/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(
        SolicitarRecuperacionContrasenaRequest $request,
        SolicitarRecuperacionContrasenaAction $solicitarRecuperacion
    ): RedirectResponse {
        $solicitarRecuperacion->execute($request->correo());

        return back()->with('status', 'Si el correo existe, enviaremos un enlace de recuperacion.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('casos-uso/cu01-gestionar-acceso/reset-password', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function update(
        RestablecerContrasenaRequest $request,
        RestablecerContrasenaAction $restablecerContrasena
    ): RedirectResponse {
        $restablecido = $restablecerContrasena->execute(
            $request->correo(),
            $request->tokenRecuperacion(),
            $request->nuevaContrasena()
        );

        if (! $restablecido) {
            throw ValidationException::withMessages([
                'email' => 'El enlace de recuperacion no es valido o ya expiro.',
            ]);
        }

        return to_route('login')->with('status', 'Contrasena restablecida correctamente.');
    }
}
