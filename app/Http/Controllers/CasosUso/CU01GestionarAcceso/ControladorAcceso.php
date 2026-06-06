<?php

namespace App\Http\Controllers\CasosUso\CU01GestionarAcceso;

use App\Actions\CasosUso\CU01GestionarAcceso\IniciarSesionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CasosUso\CU01GestionarAcceso\IniciarSesionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ControladorAcceso extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('casos-uso/cu01-gestionar-acceso/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(IniciarSesionRequest $request, IniciarSesionAction $iniciarSesion): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (! $iniciarSesion->execute($request->correo(), $request->contrasena(), $request->recordar())) {
            $request->hitRateLimit();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->clearRateLimit();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
