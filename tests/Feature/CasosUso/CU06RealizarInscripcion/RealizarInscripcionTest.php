<?php

namespace Tests\Feature\CasosUso\CU06RealizarInscripcion;

use App\Actions\CasosUso\CU06RealizarInscripcion\CrearCheckoutInscripcionAction;
use App\Actions\CasosUso\CU06RealizarInscripcion\RegistrarInscripcionAction;
use App\Models\GestionCup;
use App\Models\Inscripcion;
use App\Models\PagoInscripcion;
use App\Models\RequisitoInscripcion;
use App\Models\TurnoGestionCup;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class RealizarInscripcionTest extends TestCase
{
    use RefreshDatabase;

    public function test_postulante_puede_ver_formulario_de_inscripcion(): void
    {
        $this->withoutVite();
        $this->crearGestionCup();

        $this->get(route('cu06.inscripcion.create'))
            ->assertOk();
    }

    public function test_postulante_puede_confirmar_inscripcion_sin_requisito_de_pago(): void
    {
        $gestionCup = $this->crearGestionCup(conPago: false);

        $response = $this->post(route('cu06.inscripcion.store'), $this->payload($gestionCup))
            ->assertSessionHasNoErrors();

        $inscripcion = Inscripcion::query()->firstOrFail();

        $response->assertRedirect(route('cu06.inscripcion.resultado', $inscripcion));

        $this->assertDatabaseHas('inscripciones', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'estado' => Inscripcion::ESTADO_CONFIRMADA,
            'carrera_primera_id' => $gestionCup->carreras()->firstOrFail()->id_carrera_cup,
        ]);

        $this->assertDatabaseHas('usuarios', [
            'ci' => '99887766',
            'correo' => 'postulante@cup.test',
            'rol' => Usuario::ROL_POSTULANTE,
            'estado' => Usuario::ESTADO_INACTIVO,
        ]);
    }

    public function test_postulante_con_requisito_de_pago_es_redirigido_a_stripe_checkout(): void
    {
        $gestionCup = $this->crearGestionCup(conPago: true);

        $checkout = Mockery::mock(CrearCheckoutInscripcionAction::class);
        $checkout->shouldReceive('execute')
            ->once()
            ->andReturn('https://checkout.stripe.test/sesion');

        $this->instance(CrearCheckoutInscripcionAction::class, $checkout);

        $this->withHeaders(['X-Inertia' => 'true'])
            ->post(route('cu06.inscripcion.store'), $this->payload($gestionCup))
            ->assertSessionHasNoErrors()
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.test/sesion');

        $this->assertSame(0, Inscripcion::query()->count());
        $this->assertDatabaseMissing('postulantes', ['ci' => '99887766']);
        $this->assertSame(0, PagoInscripcion::query()->count());
    }

    public function test_cancelar_pago_regresa_al_formulario_con_datos_temporales(): void
    {
        $gestionCup = $this->crearGestionCup(conPago: true);
        $tokenBorrador = 'borrador-test';

        Cache::put(
            CrearCheckoutInscripcionAction::cacheKey($tokenBorrador),
            $this->datosInscripcion($gestionCup),
            now()->addMinutes(15)
        );

        $this->get(route('cu06.inscripcion.pago.cancelado', ['borrador' => $tokenBorrador]))
            ->assertRedirect(route('cu06.inscripcion.create'));

        $this->assertSame('postulante@cup.test', session('cu06.inscripcion.datos_previos.correo'));
        $this->assertNull(Cache::get(CrearCheckoutInscripcionAction::cacheKey($tokenBorrador)));
        $this->assertSame(0, Inscripcion::query()->count());
        $this->assertDatabaseMissing('postulantes', ['ci' => '99887766']);
        $this->assertSame(0, PagoInscripcion::query()->count());
    }

    public function test_datos_de_postulante_se_guardan_recien_con_pago_confirmado(): void
    {
        $gestionCup = $this->crearGestionCup(conPago: true);

        $inscripcion = app(RegistrarInscripcionAction::class)->execute(
            $this->datosInscripcion($gestionCup),
            [
                'session_id' => 'cs_test_pago_confirmado',
                'payment_intent' => 'pi_test_pago_confirmado',
            ]
        );

        $this->assertSame(1, Inscripcion::query()->count());
        $this->assertDatabaseHas('postulantes', [
            'ci' => '99887766',
            'correo' => 'postulante@cup.test',
            'estado' => 'inscrito',
        ]);
        $this->assertDatabaseHas('inscripciones', [
            'id_inscripcion' => $inscripcion->id_inscripcion,
            'estado' => Inscripcion::ESTADO_CONFIRMADA,
        ]);
        $this->assertDatabaseHas('pagos_inscripcion', [
            'inscripcion_id' => $inscripcion->id_inscripcion,
            'proveedor' => PagoInscripcion::PROVEEDOR_STRIPE,
            'monto_centavos' => 100000,
            'moneda' => GestionCup::MONEDA_BOB,
            'estado' => PagoInscripcion::ESTADO_PAGADO,
            'stripe_checkout_session_id' => 'cs_test_pago_confirmado',
            'stripe_payment_intent_id' => 'pi_test_pago_confirmado',
            'codigo_comprobante' => 'pi_test_pago_confirmado',
        ]);
        $this->assertDatabaseHas('usuarios', [
            'ci' => '99887766',
            'correo' => 'postulante@cup.test',
            'rol' => Usuario::ROL_POSTULANTE,
            'estado' => Usuario::ESTADO_INACTIVO,
        ]);
    }

    private function crearGestionCup(bool $conPago = true): GestionCup
    {
        $coordinador = Usuario::factory()->coordinador()->create();

        $gestionCup = GestionCup::factory()->create([
            'usuario_responsable_id' => $coordinador->id_usuario,
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);

        $gestionCup->carreras()->createMany([
            [
                'nombre_carrera' => 'Ingenieria de Sistemas',
                'cupo_disponible' => 120,
                'cupo_ocupado' => 0,
                'estado' => Usuario::ESTADO_ACTIVO,
            ],
            [
                'nombre_carrera' => 'Ingenieria Informatica',
                'cupo_disponible' => 100,
                'cupo_ocupado' => 0,
                'estado' => Usuario::ESTADO_ACTIVO,
            ],
        ]);

        $gestionCup->turnos()->create([
            'turno' => TurnoGestionCup::TURNO_MANANA,
            'orden' => 1,
            'capacidad_maxima' => 140,
            'modalidad' => TurnoGestionCup::MODALIDAD_PRESENCIAL,
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);

        $gestionCup->requisitos()->create([
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

        return $gestionCup->load(['turnos', 'carreras', 'requisitos']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(GestionCup $gestionCup): array
    {
        $requisitoDeclarativo = $gestionCup->requisitos
            ->firstWhere('tipo_requisito', RequisitoInscripcion::TIPO_DECLARATIVO);

        return [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'ci' => '99887766',
            'nombres' => 'Ana Maria',
            'apellidos' => 'Rojas Perez',
            'correo' => 'postulante@cup.test',
            'telefono' => '70000000',
            'colegio_procedencia' => 'Colegio Nacional',
            'anio_bachillerato' => '2025',
            'es_extranjero' => false,
            'turno_gestion_cup_id' => $gestionCup->turnos[0]->id_turno_gestion,
            'carrera_primera_id' => $gestionCup->carreras[0]->id_carrera_cup,
            'carrera_segunda_id' => $gestionCup->carreras[1]->id_carrera_cup,
            'requisitos_cumplidos' => [(string) $requisitoDeclarativo->id_requisito],
        ];
    }

    /**
     * @return array{
     *     gestion_cup_id: int,
     *     postulante: array<string, mixed>,
     *     turno_gestion_cup_id: int,
     *     carrera_primera_id: int,
     *     carrera_segunda_id: int|null,
     *     requisitos_cumplidos: list<int>
     * }
     */
    private function datosInscripcion(GestionCup $gestionCup): array
    {
        $payload = $this->payload($gestionCup);

        return [
            'gestion_cup_id' => (int) $payload['gestion_cup_id'],
            'postulante' => [
                'ci' => $payload['ci'],
                'nombres' => $payload['nombres'],
                'apellidos' => $payload['apellidos'],
                'correo' => $payload['correo'],
                'telefono' => $payload['telefono'],
                'colegio_procedencia' => $payload['colegio_procedencia'],
                'anio_bachillerato' => (int) $payload['anio_bachillerato'],
                'es_extranjero' => $payload['es_extranjero'],
                'estado' => 'registrado',
            ],
            'turno_gestion_cup_id' => (int) $payload['turno_gestion_cup_id'],
            'carrera_primera_id' => (int) $payload['carrera_primera_id'],
            'carrera_segunda_id' => (int) $payload['carrera_segunda_id'],
            'requisitos_cumplidos' => collect($payload['requisitos_cumplidos'])->map(fn (mixed $id): int => (int) $id)->values()->all(),
        ];
    }
}
