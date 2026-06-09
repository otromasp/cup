<?php

namespace Tests\Feature\CasosUso\CU03ConfigurarGestionCup;

use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurarGestionCupTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinador_puede_consultar_gestiones_cup(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        GestionCup::factory()->create(['usuario_responsable_id' => $coordinador->id_usuario]);

        $this->actingAs($coordinador)
            ->get(route('cu03.gestion-cup.index'))
            ->assertOk();
    }

    public function test_usuario_sin_permiso_no_puede_configurar_gestion_cup(): void
    {
        $postulante = Usuario::factory()->create(['rol' => Usuario::ROL_POSTULANTE]);

        $this->actingAs($postulante)
            ->get(route('cu03.gestion-cup.index'))
            ->assertForbidden();
    }

    public function test_coordinador_puede_registrar_configuracion_cup(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();

        $response = $this->actingAs($coordinador)
            ->post(route('cu03.gestion-cup.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $gestionCup = GestionCup::query()->where('nombre_gestion', 'Cursos CUP Preuniversitarios')->firstOrFail();

        $response->assertRedirect(route('cu03.gestion-cup.edit', $gestionCup));

        $this->assertDatabaseHas('gestiones_cup', [
            'id_gestion' => $gestionCup->id_gestion,
            'usuario_responsable_id' => $coordinador->id_usuario,
            'convocatoria' => '2-2026',
            'costo_inscripcion' => 1000,
            'moneda_inscripcion' => GestionCup::MONEDA_BOB,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);

        $this->assertDatabaseHas('carreras_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_carrera' => 'Ingenieria de Sistemas',
            'cupo_disponible' => 120,
        ]);

        $this->assertDatabaseHas('turnos_gestion_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'turno' => TurnoGestionCup::TURNO_MANANA,
            'capacidad_maxima' => 140,
            'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
        ]);

        $this->assertDatabaseHas('materias_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_materia' => 'Computacion',
            'ponderacion_nota3' => 40,
        ]);

        $this->assertDatabaseHas('requisitos_inscripcion', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_requisito' => 'Comprobante de pago',
            'obligatorio' => true,
            'tipo_requisito' => RequisitoInscripcion::TIPO_PAGO,
            'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
        ]);
    }

    public function test_no_permite_ponderaciones_que_no_suman_cien(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $payload = $this->payload([
            'materias' => [
                [
                    'nombre_materia' => 'Computacion',
                    'ponderacion_nota1' => '30',
                    'ponderacion_nota2' => '30',
                    'ponderacion_nota3' => '30',
                    'nota_minima' => '60',
                    'estado' => Usuario::ESTADO_ACTIVO,
                ],
            ],
        ]);

        $this->actingAs($coordinador)
            ->post(route('cu03.gestion-cup.store'), $payload)
            ->assertSessionHasErrors('materias.0.ponderacion_nota1');

        $this->assertDatabaseMissing('gestiones_cup', [
            'nombre_gestion' => 'Cursos CUP Preuniversitarios',
        ]);
    }

    public function test_no_permite_fecha_fin_anterior_a_fecha_inicio(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();

        $this->actingAs($coordinador)
            ->post(route('cu03.gestion-cup.store'), $this->payload([
                'fecha_inicio' => '2026-06-08',
                'fecha_fin' => '2026-06-07',
            ]))
            ->assertSessionHasErrors([
                'fecha_fin' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]);
    }

    public function test_coordinador_puede_actualizar_configuracion_cup(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = GestionCup::factory()->create(['usuario_responsable_id' => $coordinador->id_usuario]);

        $gestionCup->carreras()->create([
            'nombre_carrera' => 'Carrera anterior',
            'cupo_disponible' => 30,
            'cupo_ocupado' => 0,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        $this->actingAs($coordinador)
            ->put(route('cu03.gestion-cup.update', $gestionCup), $this->payload([
                'nombre_gestion' => 'CUP Actualizado',
                'carreras' => [
                    [
                        'nombre_carrera' => 'Ingenieria Informatica',
                        'cupo_disponible' => '80',
                        'estado' => Usuario::ESTADO_ACTIVO,
                    ],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $gestionCup->refresh();

        $this->assertSame('CUP Actualizado', $gestionCup->nombre_gestion);
        $this->assertDatabaseMissing('carreras_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_carrera' => 'Carrera anterior',
        ]);
        $this->assertDatabaseHas('carreras_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_carrera' => 'Ingenieria Informatica',
            'cupo_disponible' => 80,
        ]);
    }

    public function test_actualizar_gestion_con_inscripciones_conserva_registros_referenciados(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = GestionCup::factory()->create(['usuario_responsable_id' => $coordinador->id_usuario]);

        $turno = $gestionCup->turnos()->create([
            'turno' => TurnoGestionCup::TURNO_MANANA,
            'orden' => 1,
            'capacidad_maxima' => 70,
            'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
        $carrera = $gestionCup->carreras()->create([
            'nombre_carrera' => 'Ingenieria de Sistemas',
            'cupo_disponible' => 120,
            'cupo_ocupado' => 1,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
        $materia = $gestionCup->materias()->create([
            'nombre_materia' => 'Computacion',
            'ponderacion_nota1' => 30,
            'ponderacion_nota2' => 30,
            'ponderacion_nota3' => 40,
            'nota_minima' => 60,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
        $requisito = $gestionCup->requisitos()->create([
            'nombre_requisito' => 'Comprobante de pago',
            'obligatorio' => true,
            'tipo_requisito' => RequisitoInscripcion::TIPO_PAGO,
            'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
        $postulante = Postulante::query()->create([
            'ci' => '90000001',
            'nombres' => 'Postulante',
            'apellidos' => 'Demo',
            'correo' => 'postulante.demo.actualizacion@cup.test',
            'estado' => 'inscrito',
        ]);

        Inscripcion::query()->create([
            'gestion_cup_id' => $gestionCup->id_gestion,
            'postulante_id' => $postulante->id_postulante,
            'carrera_primera_id' => $carrera->id_carrera_cup,
            'carrera_segunda_id' => null,
            'turno_gestion_cup_id' => $turno->id_turno_gestion,
            'codigo_inscripcion' => 'CUP-REFERENCIA-001',
            'estado' => Inscripcion::ESTADO_CONFIRMADA,
            'fecha_inscripcion' => now(),
        ]);

        $this->actingAs($coordinador)
            ->put(route('cu03.gestion-cup.update', $gestionCup), $this->payload([
                'costo_inscripcion' => '699.96',
                'turnos' => [[
                    'id_turno_gestion' => $turno->id_turno_gestion,
                    'turno' => TurnoGestionCup::TURNO_MANANA,
                    'orden' => '1',
                    'capacidad_maxima' => '90',
                    'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
                    'estado' => Usuario::ESTADO_ACTIVO,
                ]],
                'carreras' => [[
                    'id_carrera_cup' => $carrera->id_carrera_cup,
                    'nombre_carrera' => 'Ingenieria de Sistemas',
                    'cupo_disponible' => '130',
                    'estado' => Usuario::ESTADO_ACTIVO,
                ]],
                'materias' => [[
                    'id_materia_cup' => $materia->id_materia_cup,
                    'nombre_materia' => 'Computacion',
                    'ponderacion_nota1' => '30',
                    'ponderacion_nota2' => '30',
                    'ponderacion_nota3' => '40',
                    'nota_minima' => '60',
                    'estado' => Usuario::ESTADO_ACTIVO,
                ]],
                'requisitos' => [[
                    'id_requisito' => $requisito->id_requisito,
                    'nombre_requisito' => 'Comprobante de pago',
                    'obligatorio' => true,
                    'tipo_requisito' => RequisitoInscripcion::TIPO_PAGO,
                    'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
                    'estado' => Usuario::ESTADO_ACTIVO,
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('carreras_cup', [
            'id_carrera_cup' => $carrera->id_carrera_cup,
            'cupo_disponible' => 130,
        ]);
        $this->assertDatabaseHas('inscripciones', [
            'carrera_primera_id' => $carrera->id_carrera_cup,
            'turno_gestion_cup_id' => $turno->id_turno_gestion,
        ]);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function payload(array $sobrescribir = []): array
    {
        return array_replace_recursive([
            'nombre_gestion' => 'Cursos CUP Preuniversitarios',
            'convocatoria' => '2-2026',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-09-30',
            'nota_minima_aprobacion' => '60',
            'costo_inscripcion' => '1000',
            'moneda_inscripcion' => GestionCup::MONEDA_BOB,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
            'turnos' => [
                [
                    'turno' => TurnoGestionCup::TURNO_MANANA,
                    'capacidad_maxima' => '140',
                    'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
                    'estado' => Usuario::ESTADO_ACTIVO,
                ],
            ],
            'carreras' => [
                [
                    'nombre_carrera' => 'Ingenieria de Sistemas',
                    'cupo_disponible' => '120',
                    'estado' => Usuario::ESTADO_ACTIVO,
                ],
            ],
            'materias' => [
                [
                    'nombre_materia' => 'Computacion',
                    'ponderacion_nota1' => '30',
                    'ponderacion_nota2' => '30',
                    'ponderacion_nota3' => '40',
                    'nota_minima' => '60',
                    'estado' => Usuario::ESTADO_ACTIVO,
                ],
            ],
            'requisitos' => [
                [
                    'nombre_requisito' => 'Comprobante de pago',
                    'obligatorio' => true,
                    'tipo_requisito' => RequisitoInscripcion::TIPO_PAGO,
                    'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
                    'estado' => Usuario::ESTADO_ACTIVO,
                ],
            ],
        ], $sobrescribir);
    }
}
