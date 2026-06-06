<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarAdministrador
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless($usuario instanceof Usuario && $usuario->esAdministrador(), 403);

        return $next($request);
    }
}
