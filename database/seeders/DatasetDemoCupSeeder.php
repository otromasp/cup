<?php

namespace Database\Seeders;

use App\Models\CarreraCup;
use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\Inscripcion;
use App\Models\InscripcionRequisito;
use App\Models\MateriaCup;
use App\Models\PagoInscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatasetDemoCupSeeder extends Seeder
{
    private const PASSWORD_DEMO = 'CupDemo2026$';

    private const POSTULANTES_DEMO = 185;

    private string $contrasenaHash;

    /**
     * Seed the application's demo dataset.
     */
    public function run(): void
    {
        $this->contrasenaHash = Hash::make(self::PASSWORD_DEMO);

        DB::transaction(function (): void {
            $administrador = $this->crearUsuarioBase(
                ci: '70000000',
                nombre: 'Administrador Demo CUP',
                correo: 'admin.demo@cup.test',
                rol: Usuario::ROL_ADMINISTRADOR
            );

            $coordinador = $this->crearUsuarioBase(
                ci: '70000001',
                nombre: 'Coordinador Demo CUP',
                correo: 'coordinador.demo@cup.test',
                rol: Usuario::ROL_COORDINADOR
            );

            $gestionCup = $this->crearGestionCup($coordinador);
            $this->limpiarDatosVolatiles($gestionCup);

            $turnos = $this->crearTurnos($gestionCup);
            $carreras = $this->crearCarreras($gestionCup);
            $materias = $this->crearMaterias($gestionCup);
            $requisitos = $this->crearRequisitos($gestionCup);
            $this->crearEtapas($gestionCup);
            $this->crearDocentes($materias);
            $this->crearPostulantesInscritos($gestionCup, $turnos, $carreras, $requisitos);

            $administrador->touch();
        });
    }

    private function crearUsuarioBase(string $ci, string $nombre, string $correo, string $rol): Usuario
    {
        return Usuario::query()->updateOrCreate([
            'correo' => $correo,
        ], [
            'ci' => $ci,
            'nombre' => $nombre,
            'contrasena_hash' => $this->contrasenaHash,
            'estado' => Usuario::ESTADO_ACTIVO,
            'rol' => $rol,
            'correo_verificado_en' => now(),
        ]);
    }

    private function crearGestionCup(Usuario $coordinador): GestionCup
    {
        return GestionCup::query()->updateOrCreate([
            'nombre_gestion' => 'CUP Dataset Demo',
            'convocatoria' => 'Demo 1-2026',
        ], [
            'usuario_responsable_id' => $coordinador->id_usuario,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonths(2)->toDateString(),
            'nota_minima_aprobacion' => 60,
            'costo_inscripcion' => 1000,
            'moneda_inscripcion' => GestionCup::MONEDA_BOB,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);
    }

    private function limpiarDatosVolatiles(GestionCup $gestionCup): void
    {
        GrupoCup::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->delete();

        Inscripcion::query()
            ->where('gestion_cup_id', $gestionCup->id_gestion)
            ->delete();

        Postulante::query()
            ->where('correo', 'like', 'postulante.demo.%@cup.test')
            ->delete();

        Usuario::query()
            ->where('rol', Usuario::ROL_POSTULANTE)
            ->where('correo', 'like', 'postulante.demo.%@cup.test')
            ->delete();
    }

    /**
     * @return list<TurnoGestionCup>
     */
    private function crearTurnos(GestionCup $gestionCup): array
    {
        $turnos = [
            [TurnoGestionCup::TURNO_MANANA, 1, 140, TurnoGestionCup::MODALIDAD_PRESENCIAL],
            [TurnoGestionCup::TURNO_TARDE, 2, 140, TurnoGestionCup::MODALIDAD_PRESENCIAL],
            [TurnoGestionCup::TURNO_NOCHE, 3, 70, TurnoGestionCup::MODALIDAD_PRESENCIAL],
        ];

        return collect($turnos)
            ->map(fn (array $turno): TurnoGestionCup => TurnoGestionCup::query()->updateOrCreate([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'turno' => $turno[0],
            ], [
                'orden' => $turno[1],
                'capacidad_maxima' => $turno[2],
                'modalidad' => $turno[3],
                'estado' => Usuario::ESTADO_ACTIVO,
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<CarreraCup>
     */
    private function crearCarreras(GestionCup $gestionCup): array
    {
        $datos = [
            ['Ingenieria de Sistemas', 120],
            ['Ingenieria Informatica', 100],
            ['Redes y Telecomunicaciones', 70],
            ['Robotica', 60],
        ];

        return collect($datos)
            ->map(fn (array $carrera): CarreraCup => CarreraCup::query()->updateOrCreate([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'nombre_carrera' => $carrera[0],
            ], [
                'cupo_disponible' => $carrera[1],
                'cupo_ocupado' => 0,
                'estado' => Usuario::ESTADO_ACTIVO,
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<MateriaCup>
     */
    private function crearMaterias(GestionCup $gestionCup): array
    {
        $materias = ['Computacion', 'Matematica', 'Ingles', 'Fisica'];

        return collect($materias)
            ->map(fn (string $nombreMateria): MateriaCup => MateriaCup::query()->updateOrCreate([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'nombre_materia' => $nombreMateria,
            ], [
                'ponderacion_nota1' => 30,
                'ponderacion_nota2' => 30,
                'ponderacion_nota3' => 40,
                'nota_minima' => 60,
                'estado' => Usuario::ESTADO_ACTIVO,
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<RequisitoInscripcion>
     */
    private function crearRequisitos(GestionCup $gestionCup): array
    {
        $datos = [
            ['Original y fotocopia del titulo de bachiller', RequisitoInscripcion::TIPO_DECLARATIVO, RequisitoInscripcion::APLICA_TODOS],
            ['Fotocopia de carnet de identidad', RequisitoInscripcion::TIPO_DECLARATIVO, RequisitoInscripcion::APLICA_TODOS],
            ['Formulario de preinscripcion', RequisitoInscripcion::TIPO_DECLARATIVO, RequisitoInscripcion::APLICA_TODOS],
            ['Libreta o certificado de ultimo ano de secundaria', RequisitoInscripcion::TIPO_DECLARATIVO, RequisitoInscripcion::APLICA_TODOS],
            ['Comprobante de pago', RequisitoInscripcion::TIPO_PAGO, RequisitoInscripcion::APLICA_TODOS],
            ['Certificado de radicatoria emitido por Migracion', RequisitoInscripcion::TIPO_DECLARATIVO, RequisitoInscripcion::APLICA_EXTRANJEROS],
        ];

        return collect($datos)
            ->map(fn (array $requisito): RequisitoInscripcion => RequisitoInscripcion::query()->updateOrCreate([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'nombre_requisito' => $requisito[0],
            ], [
                'obligatorio' => true,
                'tipo_requisito' => $requisito[1],
                'aplica_a' => $requisito[2],
                'estado' => Usuario::ESTADO_ACTIVO,
            ]))
            ->values()
            ->all();
    }

    private function crearEtapas(GestionCup $gestionCup): void
    {
        $fechaBase = now();
        $etapas = [
            ['Inscripcion', 1, $fechaBase->copy()->subDays(1), $fechaBase->copy()->addDays(30), EtapaGestionCup::ESTADO_ACTIVA],
            ['Planificacion academica', 2, $fechaBase->copy()->addDays(31), $fechaBase->copy()->addDays(40), EtapaGestionCup::ESTADO_PROGRAMADA],
            ['Clases CUP', 3, $fechaBase->copy()->addDays(41), $fechaBase->copy()->addWeeks(12), EtapaGestionCup::ESTADO_PROGRAMADA],
            ['Evaluacion y admision', 4, $fechaBase->copy()->addWeeks(13), $fechaBase->copy()->addWeeks(14), EtapaGestionCup::ESTADO_PROGRAMADA],
        ];

        foreach ($etapas as [$nombre, $orden, $inicio, $fin, $estado]) {
            EtapaGestionCup::query()->updateOrCreate([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'nombre_etapa' => $nombre,
            ], [
                'orden' => $orden,
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'estado_etapa' => $estado,
            ]);
        }
    }

    /**
     * @param  list<MateriaCup>  $materias
     */
    private function crearDocentes(array $materias): void
    {
        $docentes = [
            ['71000001', 'Ana Maria', 'Roca Salvatierra', 'Computacion', [0, 1]],
            ['71000002', 'Luis Fernando', 'Vargas Molina', 'Matematica', [1, 3]],
            ['71000003', 'Carla Andrea', 'Mendez Suarez', 'Ingles', [2]],
            ['71000004', 'Jorge Miguel', 'Rivero Paz', 'Fisica', [3, 0]],
            ['71000005', 'Valeria', 'Antelo Rojas', 'Computacion', [0, 2]],
            ['71000006', 'Marco Antonio', 'Gutierrez Leon', 'Matematica', [1]],
            ['71000007', 'Daniela', 'Cortez Quiroga', 'Ingles', [2, 3]],
            ['71000008', 'Roberto', 'Salazar Cuellar', 'Fisica', [3]],
        ];

        foreach ($docentes as [$ci, $nombres, $apellidos, $area, $materiasAsignadas]) {
            $usuario = Usuario::query()->updateOrCreate([
                'correo' => "docente.demo.{$ci}@cup.test",
            ], [
                'ci' => $ci,
                'nombre' => "{$nombres} {$apellidos}",
                'contrasena_hash' => $this->contrasenaHash,
                'estado' => Usuario::ESTADO_ACTIVO,
                'rol' => Usuario::ROL_DOCENTE,
                'correo_verificado_en' => now(),
            ]);

            $docente = Docente::query()->updateOrCreate([
                'ci' => $ci,
            ], [
                'usuario_id' => $usuario->id_usuario,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => "docente.demo.{$ci}@cup.test",
                'telefono' => '7800'.substr($ci, -4),
                'profesion' => 'Ingeniero',
                'area_especialidad' => $area,
                'titulo_profesional_afin' => true,
                'tiene_maestria' => true,
                'tiene_diplomado_educacion_superior' => true,
                'maximo_grupos_asignables' => 4,
                'estado_contratacion' => Docente::ESTADO_HABILITADO,
            ]);

            $docente->materias()->sync(
                collect($materiasAsignadas)
                    ->map(fn (int $indice): int => $materias[$indice]->id_materia_cup)
                    ->all()
            );

            $docente->disponibilidades()->delete();
            $docente->disponibilidades()->createMany([
                $this->disponibilidad(DisponibilidadDocente::DIA_LUNES, DisponibilidadDocente::TURNO_MANANA, '08:00', '12:00', DisponibilidadDocente::MODALIDAD_PRESENCIAL),
                $this->disponibilidad(DisponibilidadDocente::DIA_MARTES, DisponibilidadDocente::TURNO_MANANA, '08:00', '12:00', DisponibilidadDocente::MODALIDAD_PRESENCIAL),
                $this->disponibilidad(DisponibilidadDocente::DIA_MIERCOLES, DisponibilidadDocente::TURNO_TARDE, '14:00', '18:00', DisponibilidadDocente::MODALIDAD_MIXTA),
                $this->disponibilidad(DisponibilidadDocente::DIA_JUEVES, DisponibilidadDocente::TURNO_NOCHE, '18:00', '21:00', DisponibilidadDocente::MODALIDAD_VIRTUAL),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function disponibilidad(string $dia, string $turno, string $inicio, string $fin, string $modalidad): array
    {
        return [
            'dia_semana' => $dia,
            'turno' => $turno,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'modalidad' => $modalidad,
            'observacion' => 'Dataset demo',
        ];
    }

    /**
     * @param  list<CarreraCup>  $carreras
     * @param  list<RequisitoInscripcion>  $requisitos
     */
    private function crearPostulantesInscritos(GestionCup $gestionCup, array $turnos, array $carreras, array $requisitos): void
    {
        for ($indice = 1; $indice <= self::POSTULANTES_DEMO; $indice++) {
            $ci = (string) (80000000 + $indice);
            $correo = 'postulante.demo.'.str_pad((string) $indice, 3, '0', STR_PAD_LEFT).'@cup.test';
            $nombres = fake()->firstName();
            $apellidos = fake()->lastName().' '.fake()->lastName();

            $usuario = Usuario::query()->create([
                'ci' => $ci,
                'nombre' => "{$nombres} {$apellidos}",
                'correo' => $correo,
                'contrasena_hash' => $this->contrasenaHash,
                'estado' => Usuario::ESTADO_INACTIVO,
                'rol' => Usuario::ROL_POSTULANTE,
                'correo_verificado_en' => now(),
            ]);

            $postulante = Postulante::query()->create([
                'usuario_id' => $usuario->id_usuario,
                'ci' => $ci,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => $correo,
                'telefono' => '7600'.str_pad((string) $indice, 4, '0', STR_PAD_LEFT),
                'colegio_procedencia' => 'Colegio Demo '.(($indice % 12) + 1),
                'anio_bachillerato' => 2025,
                'es_extranjero' => $indice % 25 === 0,
                'estado' => Postulante::ESTADO_INSCRITO,
            ]);

            $primeraCarrera = $carreras[$indice % count($carreras)];
            $segundaCarrera = $carreras[($indice + 1) % count($carreras)];
            $turno = $this->turnoParaIndice($turnos, $indice);

            $inscripcion = Inscripcion::query()->create([
                'gestion_cup_id' => $gestionCup->id_gestion,
                'postulante_id' => $postulante->id_postulante,
                'carrera_primera_id' => $primeraCarrera->id_carrera_cup,
                'carrera_segunda_id' => $segundaCarrera->id_carrera_cup,
                'turno_gestion_cup_id' => $turno->id_turno_gestion,
                'codigo_inscripcion' => 'CUP-DEMO-'.str_pad((string) $indice, 4, '0', STR_PAD_LEFT),
                'estado' => Inscripcion::ESTADO_CONFIRMADA,
                'fecha_inscripcion' => now()->subDays(random_int(1, 10))->setTime(random_int(8, 18), random_int(0, 59)),
                'observacion' => 'Inscripcion confirmada por dataset demo.',
            ]);

            $this->crearCumplimientos($inscripcion, $requisitos, (bool) $postulante->es_extranjero);

            $inscripcion->pago()->create([
                'proveedor' => PagoInscripcion::PROVEEDOR_STRIPE,
                'monto_centavos' => $gestionCup->montoInscripcionCentavos(),
                'moneda' => $gestionCup->monedaInscripcionStripe(),
                'estado' => PagoInscripcion::ESTADO_PAGADO,
                'stripe_checkout_session_id' => 'cs_demo_'.$indice,
                'stripe_payment_intent_id' => 'pi_demo_'.$indice,
                'codigo_comprobante' => 'DEMO-PAGO-'.str_pad((string) $indice, 4, '0', STR_PAD_LEFT),
                'pagado_en' => $inscripcion->fecha_inscripcion,
            ]);
        }

        foreach ($carreras as $carrera) {
            $ocupado = Inscripcion::query()
                ->where('gestion_cup_id', $gestionCup->id_gestion)
                ->where('carrera_primera_id', $carrera->id_carrera_cup)
                ->count();

            $carrera->forceFill(['cupo_ocupado' => $ocupado])->save();
        }
    }

    /**
     * @param  list<TurnoGestionCup>  $turnos
     */
    private function turnoParaIndice(array $turnos, int $indice): TurnoGestionCup
    {
        $acumulado = 0;

        foreach ($turnos as $turno) {
            $acumulado += $turno->capacidad_maxima;

            if ($indice <= $acumulado) {
                return $turno;
            }
        }

        return $turnos[array_key_last($turnos)];
    }

    /**
     * @param  list<RequisitoInscripcion>  $requisitos
     */
    private function crearCumplimientos(Inscripcion $inscripcion, array $requisitos, bool $esExtranjero): void
    {
        foreach ($requisitos as $requisito) {
            if ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && ! $esExtranjero) {
                continue;
            }

            $inscripcion->requisitosCumplidos()->create([
                'requisito_id' => $requisito->id_requisito,
                'cumplido' => true,
                'origen' => $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO
                    ? InscripcionRequisito::ORIGEN_PAGO
                    : InscripcionRequisito::ORIGEN_DECLARATIVO,
                'cumplido_en' => $inscripcion->fecha_inscripcion,
            ]);
        }
    }
}
