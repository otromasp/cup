<?php

namespace App\Actions\CasosUso\CU08PlanificarGrupos;

use App\Actions\CasosUso\CU02AdministrarUsuario\EnviarCredencialesInicialesAction;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\InscripcionGrupo;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicarPlanificacionGruposAction
{
    public function __construct(
        private readonly EnviarCredencialesInicialesAction $enviarCredencialesIniciales,
    ) {}

    /**
     * @return array{grupos_publicados: int, usuarios_activados: int, credenciales_enviadas: int, error_envio_credenciales: ?string}
     */
    public function execute(GestionCup $gestionCup): array
    {
        /** @var Collection<int, Usuario> $usuariosParaNotificar */
        $usuariosParaNotificar = collect();

        $resultado = DB::transaction(function () use ($gestionCup, &$usuariosParaNotificar): array {
            $gestionCup = GestionCup::query()
                ->with(['grupos.inscripcionesAsignadas.inscripcion.postulante.usuario', 'grupos.asignaciones', 'materias'])
                ->lockForUpdate()
                ->findOrFail($gestionCup->id_gestion);

            $grupos = $gestionCup->grupos;
            $materiasActivas = $gestionCup->materias->where('estado', Usuario::ESTADO_ACTIVO)->values();

            $this->validarPublicacion($grupos, $materiasActivas);

            $grupos->each(function (GrupoCup $grupo): void {
                $grupo->forceFill([
                    'estado' => GrupoCup::ESTADO_PUBLICADO,
                    'publicado_en' => $grupo->publicado_en ?: now(),
                ])->save();
            });

            $usuarios = $grupos
                ->flatMap(fn (GrupoCup $grupo) => $grupo->inscripcionesAsignadas)
                ->map(fn (InscripcionGrupo $asignacion) => $asignacion->inscripcion?->postulante?->usuario)
                ->filter(fn ($usuario): bool => $usuario instanceof Usuario)
                ->unique('id_usuario')
                ->values();

            $usuarios->each(function (Usuario $usuario) use (&$usuariosParaNotificar): void {
                if ($usuario->estado !== Usuario::ESTADO_ACTIVO) {
                    $usuario->forceFill(['estado' => Usuario::ESTADO_ACTIVO])->save();
                }

                if ($usuario->credenciales_enviadas_en === null) {
                    $usuariosParaNotificar->push($usuario);
                }
            });

            return [
                'grupos_publicados' => $grupos->count(),
                'usuarios_activados' => $usuarios->count(),
            ];
        });

        $credencialesEnviadas = 0;
        $errorEnvio = null;

        foreach ($usuariosParaNotificar as $usuario) {
            $envio = $this->enviarCredencialesIniciales->execute(
                usuario: $usuario,
                actualizarContrasena: true,
            );

            if ($envio['enviado']) {
                $credencialesEnviadas++;

                continue;
            }

            $errorEnvio ??= $envio['error'];
        }

        return [
            ...$resultado,
            'credenciales_enviadas' => $credencialesEnviadas,
            'error_envio_credenciales' => $errorEnvio,
        ];
    }

    /**
     * @param  Collection<int, GrupoCup>  $grupos
     * @param  Collection<int, MateriaCup>  $materiasActivas
     */
    private function validarPublicacion(Collection $grupos, Collection $materiasActivas): void
    {
        if ($grupos->isEmpty()) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'Primero debe generar los grupos de la gestion.',
            ]);
        }

        if ($materiasActivas->isEmpty()) {
            throw ValidationException::withMessages([
                'gestion_cup_id' => 'La gestion no tiene materias activas para publicar la planificacion.',
            ]);
        }

        foreach ($grupos as $grupo) {
            if ($grupo->inscripcionesAsignadas->isEmpty()) {
                throw ValidationException::withMessages([
                    'gestion_cup_id' => "{$grupo->nombre_grupo} no tiene postulantes asignados.",
                ]);
            }

            $materiasAsignadas = $grupo->asignaciones->pluck('materia_cup_id')->unique();
            $materiasFaltantes = $materiasActivas
                ->reject(fn (MateriaCup $materia): bool => $materiasAsignadas->contains($materia->id_materia_cup))
                ->pluck('nombre_materia')
                ->implode(', ');

            if ($materiasFaltantes !== '') {
                throw ValidationException::withMessages([
                    'gestion_cup_id' => "{$grupo->nombre_grupo} aun no tiene asignacion para: {$materiasFaltantes}.",
                ]);
            }
        }
    }
}
