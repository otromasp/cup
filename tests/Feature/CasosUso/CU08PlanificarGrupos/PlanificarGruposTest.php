<?php

namespace Tests\Feature\CasosUso\CU08PlanificarGrupos;

use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\GestionCup;
use App\Models\GrupoCup;
use App\Models\Inscripcion;
use App\Models\MateriaCup;
use App\Models\Postulante;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlanificarGruposTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinador_puede_consultar_planificacion_de_grupos(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = GestionCup::factory()->create([
            'usuario_responsable_id' => $coordinador->id_usuario,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);

        $this->actingAs($coordinador)
            ->get(route('cu08.planificacion-grupos.index', ['gestion_id' => $gestionCup->id_gestion]))
            ->assertOk();
    }

    public function test_coordinador_genera_grupos_segun_capacidad_maxima(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestionConBase($coordinador);

        for ($indice = 1; $indice <= 71; $indice++) {
            $this->crearInscripcionConfirmada($gestionCup, $indice);
        }

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.generate'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('grupos_cup', 2);
        $this->assertDatabaseCount('inscripcion_grupo', 71);
    }

    public function test_coordinador_asigna_docente_horario_y_materia(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestionConBase($coordinador);
        $materia = $gestionCup->materias()->firstOrFail();
        $docente = $this->docenteHabilitadoPara($materia);
        $this->crearInscripcionConfirmada($gestionCup, 1);

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.generate'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ]);

        $grupo = GrupoCup::query()->firstOrFail();

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.assignments.store'), [
                'grupo_cup_id' => $grupo->id_grupo_cup,
                'materia_cup_id' => $materia->id_materia_cup,
                'docente_id' => $docente->id_docente,
                'dia_semana' => DisponibilidadDocente::DIA_LUNES,
                'turno' => DisponibilidadDocente::TURNO_MANANA,
                'hora_inicio' => '08:00',
                'hora_fin' => '10:00',
                'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
                'aula' => 'Aula 1',
                'enlace_clase' => null,
                'observacion' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('horarios_grupo_cup', [
            'grupo_cup_id' => $grupo->id_grupo_cup,
            'dia_semana' => DisponibilidadDocente::DIA_LUNES,
            'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
            'aula' => 'Aula 1',
        ]);

        $this->assertDatabaseHas('asignaciones_grupo', [
            'grupo_cup_id' => $grupo->id_grupo_cup,
            'materia_cup_id' => $materia->id_materia_cup,
            'docente_id' => $docente->id_docente,
        ]);
    }

    public function test_coordinador_genera_horarios_automaticamente(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestionConBase($coordinador);
        $materia = $gestionCup->materias()->firstOrFail();
        $docente = $this->docenteHabilitadoPara($materia);
        $this->crearInscripcionConfirmada($gestionCup, 1);

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.generate'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ]);

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.schedule.generate'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ])
            ->assertSessionHasNoErrors();

        $grupo = GrupoCup::query()->firstOrFail();

        $this->assertDatabaseHas('horarios_grupo_cup', [
            'grupo_cup_id' => $grupo->id_grupo_cup,
            'dia_semana' => DisponibilidadDocente::DIA_LUNES,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ]);

        $this->assertDatabaseHas('asignaciones_grupo', [
            'grupo_cup_id' => $grupo->id_grupo_cup,
            'materia_cup_id' => $materia->id_materia_cup,
            'docente_id' => $docente->id_docente,
        ]);
    }

    public function test_publicar_planificacion_activa_usuarios_asignados(): void
    {
        Notification::fake();

        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestionConBase($coordinador);
        $materia = $gestionCup->materias()->firstOrFail();
        $docente = $this->docenteHabilitadoPara($materia);
        $inscripcion = $this->crearInscripcionConfirmada($gestionCup, 1);

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.generate'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ]);

        $grupo = GrupoCup::query()->firstOrFail();

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.assignments.store'), [
                'grupo_cup_id' => $grupo->id_grupo_cup,
                'materia_cup_id' => $materia->id_materia_cup,
                'docente_id' => $docente->id_docente,
                'dia_semana' => DisponibilidadDocente::DIA_LUNES,
                'turno' => DisponibilidadDocente::TURNO_MANANA,
                'hora_inicio' => '08:00',
                'hora_fin' => '10:00',
                'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
            ]);

        $this->actingAs($coordinador)
            ->post(route('cu08.planificacion-grupos.publish'), [
                'gestion_cup_id' => $gestionCup->id_gestion,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos_cup', [
            'id_grupo_cup' => $grupo->id_grupo_cup,
            'estado' => GrupoCup::ESTADO_PUBLICADO,
        ]);

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $inscripcion->postulante->usuario_id,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
    }

    private function gestionConBase(Usuario $coordinador): GestionCup
    {
        $gestionCup = GestionCup::factory()->create([
            'usuario_responsable_id' => $coordinador->id_usuario,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);

        $gestionCup->carreras()->create([
            'nombre_carrera' => 'Ingenieria de Sistemas',
            'cupo_disponible' => 120,
            'cupo_ocupado' => 0,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        MateriaCup::factory()->create([
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_materia' => 'Computacion',
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        return $gestionCup->refresh();
    }

    private function crearInscripcionConfirmada(GestionCup $gestionCup, int $indice): Inscripcion
    {
        $usuario = Usuario::factory()->create([
            'ci' => '9000'.$indice,
            'correo' => "postulante{$indice}@cup.test",
            'estado' => Usuario::ESTADO_INACTIVO,
            'rol' => Usuario::ROL_POSTULANTE,
        ]);

        $postulante = Postulante::query()->create([
            'usuario_id' => $usuario->id_usuario,
            'ci' => '8000'.$indice,
            'nombres' => 'Postulante',
            'apellidos' => 'Numero '.$indice,
            'correo' => "postulante{$indice}@cup.test",
            'telefono' => '70000000',
            'colegio_procedencia' => 'Colegio Central',
            'anio_bachillerato' => 2025,
            'es_extranjero' => false,
            'estado' => Postulante::ESTADO_INSCRITO,
        ]);

        return Inscripcion::query()->create([
            'gestion_cup_id' => $gestionCup->id_gestion,
            'postulante_id' => $postulante->id_postulante,
            'carrera_primera_id' => $gestionCup->carreras()->firstOrFail()->id_carrera_cup,
            'carrera_segunda_id' => null,
            'codigo_inscripcion' => 'CUP-PLAN-'.$indice,
            'estado' => Inscripcion::ESTADO_CONFIRMADA,
            'fecha_inscripcion' => now(),
        ]);
    }

    private function docenteHabilitadoPara(MateriaCup $materia): Docente
    {
        $docente = Docente::factory()->habilitado()->create();
        $docente->materias()->attach($materia->id_materia_cup);
        $docente->disponibilidades()->create([
            'dia_semana' => DisponibilidadDocente::DIA_LUNES,
            'turno' => DisponibilidadDocente::TURNO_MANANA,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
            'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
            'observacion' => null,
        ]);

        return $docente->refresh();
    }
}
