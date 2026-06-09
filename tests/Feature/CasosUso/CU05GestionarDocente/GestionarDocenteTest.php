<?php

namespace Tests\Feature\CasosUso\CU05GestionarDocente;

use App\Models\DisponibilidadDocente;
use App\Models\Docente;
use App\Models\GestionCup;
use App\Models\MateriaCup;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionarDocenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinador_puede_consultar_docentes(): void
    {
        $this->withoutVite();

        $coordinador = Usuario::factory()->coordinador()->create();
        Docente::factory()->create();

        $this->actingAs($coordinador)
            ->get(route('cu05.docentes.index'))
            ->assertOk();
    }

    public function test_usuario_sin_permiso_no_puede_gestionar_docentes(): void
    {
        $postulante = Usuario::factory()->create(['rol' => Usuario::ROL_POSTULANTE]);

        $this->actingAs($postulante)
            ->get(route('cu05.docentes.index'))
            ->assertForbidden();
    }

    public function test_coordinador_puede_registrar_docente_habilitado(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $usuarioDocente = Usuario::factory()->docente()->create();
        $materia = $this->materia();

        $this->actingAs($coordinador)
            ->post(route('cu05.docentes.store'), $this->payload([
                'usuario_id' => (string) $usuarioDocente->id_usuario,
                'materias' => [(string) $materia->id_materia_cup],
            ]))
            ->assertSessionHasNoErrors();

        $docente = Docente::query()->where('ci', '13779759')->firstOrFail();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'usuario_id' => $usuarioDocente->id_usuario,
            'estado_contratacion' => Docente::ESTADO_HABILITADO,
        ]);

        $this->assertDatabaseHas('docente_materia_cup', [
            'docente_id' => $docente->id_docente,
            'materia_cup_id' => $materia->id_materia_cup,
        ]);

        $this->assertDatabaseHas('disponibilidades_docente', [
            'docente_id' => $docente->id_docente,
            'dia_semana' => DisponibilidadDocente::DIA_LUNES,
            'turno' => DisponibilidadDocente::TURNO_MANANA,
            'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
        ]);
    }

    public function test_no_permite_habilitar_docente_sin_requisitos_academicos(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $materia = $this->materia();

        $this->actingAs($coordinador)
            ->post(route('cu05.docentes.store'), $this->payload([
                'materias' => [(string) $materia->id_materia_cup],
                'titulo_profesional_afin' => false,
            ]))
            ->assertSessionHasErrors('titulo_profesional_afin');
    }

    public function test_no_permite_disponibilidad_solapada(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $materia = $this->materia();

        $this->actingAs($coordinador)
            ->post(route('cu05.docentes.store'), $this->payload([
                'materias' => [(string) $materia->id_materia_cup],
                'disponibilidades' => [
                    $this->disponibilidad(['hora_inicio' => '08:00', 'hora_fin' => '10:00']),
                    $this->disponibilidad(['hora_inicio' => '09:00', 'hora_fin' => '11:00']),
                ],
            ]))
            ->assertSessionHasErrors('disponibilidades.1.hora_inicio');
    }

    public function test_coordinador_puede_actualizar_docente(): void
    {
        $coordinador = Usuario::factory()->coordinador()->create();
        $materia = $this->materia();
        $docente = Docente::factory()->create([
            'estado_contratacion' => Docente::ESTADO_OBSERVADO,
        ]);
        $docente->materias()->attach($materia->id_materia_cup);
        $docente->disponibilidades()->create($this->disponibilidad());

        $this->actingAs($coordinador)
            ->put(route('cu05.docentes.update', $docente), $this->payload([
                'ci' => $docente->ci,
                'correo' => $docente->correo,
                'nombres' => 'Docente Actualizado',
                'materias' => [(string) $materia->id_materia_cup],
                'estado_contratacion' => Docente::ESTADO_OBSERVADO,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombres' => 'Docente Actualizado',
            'estado_contratacion' => Docente::ESTADO_OBSERVADO,
        ]);
    }

    private function materia(): MateriaCup
    {
        $gestionCup = GestionCup::factory()->create();

        return MateriaCup::factory()->create([
            'gestion_cup_id' => $gestionCup->id_gestion,
            'nombre_materia' => 'Computacion',
            'estado' => Usuario::ESTADO_ACTIVO,
        ]);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function payload(array $sobrescribir = []): array
    {
        return [
            'usuario_id' => '',
            'ci' => '13779759',
            'nombres' => 'Abel',
            'apellidos' => 'Olivera Flores',
            'correo' => 'docente.cup@example.com',
            'telefono' => '70000000',
            'profesion' => 'Ingeniero de Sistemas',
            'area_especialidad' => 'Computacion',
            'titulo_profesional_afin' => true,
            'tiene_maestria' => true,
            'tiene_diplomado_educacion_superior' => true,
            'maximo_grupos_asignables' => '4',
            'estado_contratacion' => Docente::ESTADO_HABILITADO,
            'materias' => [],
            'disponibilidades' => [$this->disponibilidad()],
            ...$sobrescribir,
        ];
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function disponibilidad(array $sobrescribir = []): array
    {
        return [
            'dia_semana' => DisponibilidadDocente::DIA_LUNES,
            'turno' => DisponibilidadDocente::TURNO_MANANA,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'modalidad' => DisponibilidadDocente::MODALIDAD_PRESENCIAL,
            'observacion' => 'Disponible para clases presenciales',
            ...$sobrescribir,
        ];
    }
}
