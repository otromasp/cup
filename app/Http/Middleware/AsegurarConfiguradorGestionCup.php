<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarConfiguradorGestionCup
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless($usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup(), 403);

        return $next($request);
    }
}
