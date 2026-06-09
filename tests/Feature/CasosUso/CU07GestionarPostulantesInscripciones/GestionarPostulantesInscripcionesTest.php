<?php

namespace Tests\Feature\CasosUso\CU07GestionarPostulantesInscripciones;

use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\PagoInscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionarPostulantesInscripcionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinador_puede_consultar_inscripciones(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        $this->crearInscripcion();

        $this->actingAs($coordinador)
            ->get(route('cu07.postulantes-inscripciones.index'))
            ->assertOk();
    }

    public function test_coordinador_puede_ver_detalle_de_inscripcion(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        $inscripcion = $this->crearInscripcion();

        $this->actingAs($coordinador)
            ->get(route('cu07.postulantes-inscripciones.show', $inscripcion))
            ->assertOk();
    }

    public function test_coordinador_puede_cancelar_inscripcion_pendiente(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $inscripcion = $this->crearInscripcion(estado: Inscripcion::ESTADO_PENDIENTE_PAGO, conPago: true);

        $this->actingAs($coordinador)
            ->from(route('cu07.postulantes-inscripciones.show', $inscripcion))
            ->patch(route('cu07.postulantes-inscripciones.status.update', $inscripcion), [
                'estado' => Inscripcion::ESTADO_CANCELADA,
                'observacion' => 'Pago no completado por el postulante.',
            ])
            ->assertRedirect(route('cu07.postulantes-inscripciones.show', $inscripcion))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inscripciones', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'estado' => Inscripcion::ESTADO_CANCELADA,
            'observacion' => 'Pago no completado por el postulante.',
        ]);

        $this->assertDatabaseHas('pagos_inscripcion', [
            'inscripcion_id' => $inscripcion->id_inscripcion,
            'estado' => PagoInscripcion::ESTADO_CANCELADO,
        ]);
    }

    public function test_no_permite_confirmar_si_el_pago_sigue_pendiente(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $inscripcion = $this->crearInscripcion(estado: Inscripcion::ESTADO_PENDIENTE_PAGO, conPago: true);

        $this->actingAs($coordinador)
            ->from(route('cu07.postulantes-inscripciones.show', $inscripcion))
            ->patch(route('cu07.postulantes-inscripciones.status.update', $inscripcion), [
                'estado' => Inscripcion::ESTADO_CONFIRMADA,
                'observacion' => 'Revision administrativa.',
            ])
            ->assertRedirect(route('cu07.postulantes-inscripciones.show', $inscripcion))
            ->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('inscripciones', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'estado' => Inscripcion::ESTADO_PENDIENTE_PAGO,
        ]);
    }

    private function crearInscripcion(string $estado = Inscripcion::ESTADO_CONFIRMADA, bool $conPago = false): Inscripcion
    {
        $coordinador = Usuario::factory()->coordinador()->create();

        $gestionCup = GestionCup::factory()->create([
            'usuario_responsable_id' => $coordinador->id_usuario,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);

        $carreraPrimera = $gestionCup->carreras()->create([
            'nombre_carrera' => 'Ingenieria de Sistemas',
            'cupo_disponible' => 120,
            'cupo_ocupado' => 0,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        $carreraSegunda = $gestionCup->carreras()->create([
            'nombre_carrera' => 'Ingenieria Informatica',
            'cupo_disponible' => 100,
            'cupo_ocupado' => 0,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        $requisito = $gestionCup->requisitos()->create([
            'nombre_requisito' => 'Fotocopia de carnet de identidad',
            'obligatorio' => true,
            'tipo_requisito' => RequisitoInscripcion::TIPO_DECLARATIVO,
            'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        if ($conPago) {
            $gestionCup->requisitos()->create([
                'nombre_requisito' => 'Comprobante de pago',
                'obligatorio' => true,
                'tipo_requisito' => RequisitoInscripcion::TIPO_PAGO,
                'aplica_a' => RequisitoInscripcion::APLICA_TODOS,
                'estado' => Usuario::ESTADO_ACTIVO,
            ]);
        }

        $postulante = Postulante::query()->create([
            'ci' => '77665544',
            'nombres' => 'Luis Alberto',
            'apellidos' => 'Mendez Roca',
            'correo' => 'postulante-cu7@cup.test',
            'telefono' => '70001111',
            'colegio_procedencia' => 'Colegio Central',
            'anio_bachillerato' => 2025,
            'es_extranjero' => false,
            'estado' => $estado === Inscripcion::ESTADO_CONFIRMADA ? Postulante::ESTADO_INSCRITO : Postulante::ESTADO_REGISTRADO,
        ]);

        $inscripcion = Inscripcion::query()->create([
            'gestion_cup_id' => $gestionCup->id_gestion,
            'postulante_id' => $postulante->id_postulante,
            'carrera_primera_id' => $carreraPrimera->id_carrera_cup,
            'carrera_segunda_id' => $carreraSegunda->id_carrera_cup,
            'codigo_inscripcion' => 'CUP-TEST-0001',
            'estado' => $estado,
            'fecha_inscripcion' => now(),
        ]);

        $inscripcion->requisitosCumplidos()->create([
            'requisito_id' => $requisito->id_requisito,
            'cumplido' => true,
            'origen' => 'declarativo',
            'cumplido_en' => now(),
        ]);

        if ($conPago) {
            $inscripcion->pago()->create([
                'proveedor' => PagoInscripcion::PROVEEDOR_STRIPE,
                'monto_centavos' => 100000,
                'moneda' => GestionCup::MONEDA_BOB,
                'estado' => PagoInscripcion::ESTADO_PENDIENTE,
            ]);
        }

        return $inscripcion;
    }
}
