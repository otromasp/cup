<?php

namespace Tests\Feature\CasosUso\CU04GestionarEtapasGestionCup;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionarEtapasGestionCupTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinador_puede_consultar_gestiones_para_etapas(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        GestionCup::factory()->create(['usuario_responsable_id' => $coordinador->id_usuario]);

        $this->actingAs($coordinador)
            ->get(route('cu04.etapas-cup.index'))
            ->assertOk();
    }

    public function test_usuario_sin_permiso_no_puede_gestionar_etapas(): void
    {
        $postulante = Usuario::factory()->create(['rol' => Usuario::ROL_POSTULANTE]);

        $this->actingAs($postulante)
            ->get(route('cu04.etapas-cup.index'))
            ->assertForbidden();
    }

    public function test_coordinador_puede_programar_etapas(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestion($coordinador);

        $this->actingAs($coordinador)
            ->put(route('cu04.etapas-cup.update', $gestionCup), $this->payloadEtapas())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('etapas_gestion_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_etapa' => 'Inscripcion',
            'orden' => 1,
            'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
        ]);

        $this->assertDatabaseHas('etapas_gestion_cup', [
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_etapa' => 'Registro de notas',
            'orden' => 4,
        ]);
    }

    public function test_no_permite_reprogramar_si_ya_inicio_una_etapa(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestion($coordinador);
        $gestionCup->etapas()->create([
            'nombre_etapa' => 'Inscripcion',
            'orden' => 1,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-15',
            'estado_etapa' => EtapaGestionCup::ESTADO_ACTIVA,
        ]);

        $this->actingAs($coordinador)
            ->put(route('cu04.etapas-cup.update', $gestionCup), $this->payloadEtapas())
            ->assertSessionHasErrors('etapas');
    }

    public function test_coordinador_puede_activar_y_cerrar_etapa(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestion($coordinador);
        $etapa = $gestionCup->etapas()->create([
            'nombre_etapa' => 'Inscripcion',
            'orden' => 1,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-15',
            'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
        ]);

        $this->actingAs($coordinador)
            ->patch(route('cu04.etapas-cup.activate', $etapa))
            ->assertRedirect(route('cu04.etapas-cup.edit', $gestionCup));

        $etapa->refresh();
        $gestionCup->refresh();

        $this->assertSame(EtapaGestionCup::ESTADO_ACTIVA, $etapa->estado_etapa);
        $this->assertSame(GestionCup::ESTADO_BLOQUEADA, $gestionCup->estado_configuracion);

        $this->actingAs($coordinador)
            ->patch(route('cu04.etapas-cup.close', $etapa))
            ->assertRedirect(route('cu04.etapas-cup.edit', $gestionCup));

        $this->assertSame(EtapaGestionCup::ESTADO_CERRADA, $etapa->refresh()->estado_etapa);
    }

    public function test_iniciar_siguiente_etapa_cierra_la_activa(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestion($coordinador);
        $primeraEtapa = $gestionCup->etapas()->create([
            'nombre_etapa' => 'Inscripcion',
            'orden' => 1,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-15',
            'estado_etapa' => EtapaGestionCup::ESTADO_ACTIVA,
        ]);
        $segundaEtapa = $gestionCup->etapas()->create([
            'nombre_etapa' => 'Planificacion academica',
            'orden' => 2,
            'fecha_inicio' => '2026-07-16',
            'fecha_fin' => '2026-07-31',
            'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
        ]);

        $this->actingAs($coordinador)
            ->patch(route('cu04.etapas-cup.activate', $segundaEtapa))
            ->assertRedirect(route('cu04.etapas-cup.edit', $gestionCup))
            ->assertSessionHasNoErrors();

        $this->assertSame(EtapaGestionCup::ESTADO_CERRADA, $primeraEtapa->refresh()->estado_etapa);
        $this->assertSame(EtapaGestionCup::ESTADO_ACTIVA, $segundaEtapa->refresh()->estado_etapa);
    }

    public function test_reabrir_etapa_reprograma_las_posteriores(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $gestionCup = $this->gestion($coordinador);
        $primeraEtapa = $gestionCup->etapas()->create([
            'nombre_etapa' => 'Inscripcion',
            'orden' => 1,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-15',
            'estado_etapa' => EtapaGestionCup::ESTADO_CERRADA,
        ]);
        $segundaEtapa = $gestionCup->etapas()->create([
            'nombre_etapa' => 'Planificacion academica',
            'orden' => 2,
            'fecha_inicio' => '2026-07-16',
            'fecha_fin' => '2026-07-31',
            'estado_etapa' => EtapaGestionCup::ESTADO_CERRADA,
        ]);

        $this->actingAs($coordinador)
            ->from(route('cu04.etapas-cup.edit', $gestionCup))
            ->patch(route('cu04.etapas-cup.reopen', $primeraEtapa))
            ->assertRedirect(route('cu04.etapas-cup.edit', $gestionCup))
            ->assertSessionHasNoErrors();

        $this->assertSame(EtapaGestionCup::ESTADO_ACTIVA, $primeraEtapa->refresh()->estado_etapa);
        $this->assertSame(EtapaGestionCup::ESTADO_PROGRAMADA, $segundaEtapa->refresh()->estado_etapa);
    }

    private function gestion(Usuario $coordinador): GestionCup
    {
        return GestionCup::factory()->create([
            'usuario_responsable_id' => $coordinador->id_usuario,
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-09-30',
            'estado_configuracion' => GestionCup::ESTADO_CONFIGURADA,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadEtapas(): array
    {
        return [
            'etapas' => [
                [
                    'nombre_etapa' => 'Inscripcion',
                    'orden' => '1',
                    'fecha_inicio' => '2026-07-01',
                    'fecha_fin' => '2026-07-15',
                    'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
                ],
                [
                    'nombre_etapa' => 'Planificacion academica',
                    'orden' => '2',
                    'fecha_inicio' => '2026-07-16',
                    'fecha_fin' => '2026-07-31',
                    'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
                ],
                [
                    'nombre_etapa' => 'Desarrollo de clases',
                    'orden' => '3',
                    'fecha_inicio' => '2026-08-01',
                    'fecha_fin' => '2026-08-31',
                    'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
                ],
                [
                    'nombre_etapa' => 'Registro de notas',
                    'orden' => '4',
                    'fecha_inicio' => '2026-09-01',
                    'fecha_fin' => '2026-09-10',
                    'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
                ],
            ],
        ];
    }
}
