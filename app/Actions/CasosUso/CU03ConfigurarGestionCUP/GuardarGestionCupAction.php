<?php

namespace App\Actions\CasosUso\CU03ConfigurarGestionCUP;

use App\Models\CarreraCup;
use App\Models\GestionCup;
use App\Models\MateriaCup;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarGestionCupAction
{
    /**
     * @param  array{
     *     gestion: array<string, mixed>,
     *     turnos: list<array<string, mixed>>,
     *     carreras: list<array<string, mixed>>,
     *     materias: list<array<string, mixed>>,
     *     requisitos: list<array<string, mixed>>
     * }  $datos
     */
    public function execute(array $datos, Usuario $actor, ?GestionCup $gestionCup = null): GestionCup
    {
        if ($gestionCup?->estado_configuracion === GestionCup::ESTADO_BLOQUEADA) {
            throw ValidationException::withMessages([
                'estado_configuracion' => 'No se puede modificar una gestion bloqueada.',
            ]);
        }

        return DB::transaction(function () use ($datos, $actor, $gestionCup): GestionCup {
            $gestionCup ??= new GestionCup;

            $gestionCup->forceFill([
                ...$datos['gestion'],
                'usuario_responsable_id' => $actor->id_usuario,
            ])->save();

            $this->sincronizarTurnos($gestionCup, $datos['turnos']);
            $this->sincronizarCarreras($gestionCup, $datos['carreras']);
            $this->sincronizarMaterias($gestionCup, $datos['materias']);
            $this->sincronizarRequisitos($gestionCup, $datos['requisitos']);

            return $gestionCup->load(['usuarioResponsable', 'turnos', 'carreras', 'materias', 'requisitos']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $turnos
     */
    private function sincronizarTurnos(GestionCup $gestionCup, array $turnos): void
    {
        /** @var EloquentCollection<int, TurnoGestionCup> $existentes */
        $existentes = $gestionCup->turnos()->get();
        $idsConservados = [];

        foreach ($turnos as $turno) {
            /** @var TurnoGestionCup|null $modelo */
            $modelo = $this->buscarModelo($existentes, 'id_turno_gestion', $turno['id_turno_gestion'] ?? null)
                ?? $this->buscarModelo($existentes, 'turno', $turno['turno']);

            $atributos = Arr::except($turno, ['id_turno_gestion']);

            if ($modelo instanceof TurnoGestionCup) {
                $modelo->forceFill($atributos)->save();
            } else {
                $modelo = $gestionCup->turnos()->create($atributos);
            }

            $idsConservados[] = $modelo->id_turno_gestion;
        }

        $this->retirarNoEnviados(
            $existentes,
            $idsConservados,
            'id_turno_gestion',
            fn (TurnoGestionCup $turno): bool => $turno->inscripciones()->exists(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $carreras
     */
    private function sincronizarCarreras(GestionCup $gestionCup, array $carreras): void
    {
        /** @var EloquentCollection<int, CarreraCup> $existentes */
        $existentes = $gestionCup->carreras()->get();
        $idsConservados = [];

        foreach ($carreras as $carrera) {
            /** @var CarreraCup|null $modelo */
            $modelo = $this->buscarModelo($existentes, 'id_carrera_cup', $carrera['id_carrera_cup'] ?? null)
                ?? $this->buscarModelo($existentes, 'nombre_carrera', $carrera['nombre_carrera']);

            $atributos = Arr::except($carrera, ['id_carrera_cup']);

            if ($modelo instanceof CarreraCup) {
                $modelo->forceFill($atributos)->save();
            } else {
                $modelo = $gestionCup->carreras()->create([
                    ...$atributos,
                    'cupo_ocupado' => 0,
                ]);
            }

            $idsConservados[] = $modelo->id_carrera_cup;
        }

        $this->retirarNoEnviados(
            $existentes,
            $idsConservados,
            'id_carrera_cup',
            fn (CarreraCup $carrera): bool => $carrera->inscripcionesPrimeraOpcion()->exists()
                || $carrera->inscripcionesSegundaOpcion()->exists(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $materias
     */
    private function sincronizarMaterias(GestionCup $gestionCup, array $materias): void
    {
        /** @var EloquentCollection<int, MateriaCup> $existentes */
        $existentes = $gestionCup->materias()->get();
        $idsConservados = [];

        foreach ($materias as $materia) {
            /** @var MateriaCup|null $modelo */
            $modelo = $this->buscarModelo($existentes, 'id_materia_cup', $materia['id_materia_cup'] ?? null)
                ?? $this->buscarModelo($existentes, 'nombre_materia', $materia['nombre_materia']);

            $atributos = Arr::except($materia, ['id_materia_cup']);

            if ($modelo instanceof MateriaCup) {
                $modelo->forceFill($atributos)->save();
            } else {
                $modelo = $gestionCup->materias()->create($atributos);
            }

            $idsConservados[] = $modelo->id_materia_cup;
        }

        $this->retirarNoEnviados(
            $existentes,
            $idsConservados,
            'id_materia_cup',
            fn (MateriaCup $materia): bool => $materia->docentes()->exists()
                || $materia->asignacionesGrupo()->exists(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $requisitos
     */
    private function sincronizarRequisitos(GestionCup $gestionCup, array $requisitos): void
    {
        /** @var EloquentCollection<int, RequisitoInscripcion> $existentes */
        $existentes = $gestionCup->requisitos()->get();
        $idsConservados = [];

        foreach ($requisitos as $requisito) {
            /** @var RequisitoInscripcion|null $modelo */
            $modelo = $this->buscarModelo($existentes, 'id_requisito', $requisito['id_requisito'] ?? null)
                ?? $this->buscarModelo($existentes, 'nombre_requisito', $requisito['nombre_requisito']);

            $atributos = Arr::except($requisito, ['id_requisito']);

            if ($modelo instanceof RequisitoInscripcion) {
                $modelo->forceFill($atributos)->save();
            } else {
                $modelo = $gestionCup->requisitos()->create($atributos);
            }

            $idsConservados[] = $modelo->id_requisito;
        }

        $this->retirarNoEnviados(
            $existentes,
            $idsConservados,
            'id_requisito',
            fn (RequisitoInscripcion $requisito): bool => $requisito->cumplimientosInscripcion()->exists(),
        );
    }

    /**
     * @param  EloquentCollection<int, Model>  $modelos
     */
    private function buscarModelo(EloquentCollection $modelos, string $campo, mixed $valor): ?Model
    {
        if (blank($valor)) {
            return null;
        }

        return $modelos->first(function (Model $modelo) use ($campo, $valor): bool {
            $actual = $modelo->getAttribute($campo);

            if (is_string($actual) || is_string($valor)) {
                return mb_strtolower((string) $actual) === mb_strtolower(trim((string) $valor));
            }

            return (int) $actual === (int) $valor;
        });
    }

    /**
     * @param  EloquentCollection<int, Model>  $existentes
     * @param  list<int>  $idsConservados
     */
    private function retirarNoEnviados(EloquentCollection $existentes, array $idsConservados, string $llave, callable $estaEnUso): void
    {
        $conservados = collect($idsConservados)->map(fn (int|string $id): int => (int) $id);

        foreach ($existentes as $modelo) {
            if ($conservados->contains((int) $modelo->getAttribute($llave))) {
                continue;
            }

            if ($estaEnUso($modelo)) {
                $modelo->forceFill(['estado' => Usuario::ESTADO_INACTIVO])->save();

                continue;
            }

            $modelo->delete();
        }
    }
}
