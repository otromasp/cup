<?php

namespace Tests\Feature\CasosUso\CU02AdministrarUsuario;

use App\Models\Usuario;
use App\Notifications\CasosUso\CU02AdministrarUsuario\EnviarCredencialesIniciales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdministrarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_puede_consultar_lista_de_usuarios(): void
    {
        $this->withoutVite();

        $administrador = Usuario::factory()->administrador()->create();
        Usuario::factory()->create(['nombre' => 'Usuario de prueba']);

        $this->actingAs($administrador)
            ->get(route('cu02.usuarios.index'))
            ->assertOk();
    }

    public function test_usuario_sin_rol_administrador_no_puede_administrar_usuarios(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COORDINADOR]);

        $this->actingAs($usuario)
            ->get(route('cu02.usuarios.index'))
            ->assertForbidden();
    }

    public function test_administrador_puede_registrar_usuario(): void
    {
        Notification::fake();

        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->post(route('cu02.usuarios.store'), [
                'nombre' => 'Coordinador CUP',
                'ci' => '1234567',
                'correo' => 'coordinador@example.com',
                'rol' => Usuario::ROL_COORDINADOR,
                'estado' => Usuario::ESTADO_ACTIVO,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('contrasena_inicial')
            ->assertRedirect(route('cu02.usuarios.index'));

        $usuario = Usuario::query()->where('correo', 'coordinador@example.com')->firstOrFail();

        $this->assertSame('Coordinador CUP', $usuario->nombre);
        $this->assertSame(Usuario::ROL_COORDINADOR, $usuario->rol);
        $this->assertTrue(Hash::check('password', $usuario->contrasena_hash));
        $this->assertNotNull($usuario->credenciales_enviadas_en);

        Notification::assertSentTo(
            $usuario,
            EnviarCredencialesIniciales::class,
            fn (EnviarCredencialesIniciales $notification): bool => $notification->correo === 'coordinador@example.com'
                && $notification->contrasenaInicial === 'password',
        );
    }

    public function test_administrador_puede_registrar_usuario_con_contrasena_generada(): void
    {
        Notification::fake();

        $administrador = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($administrador)
            ->post(route('cu02.usuarios.store'), [
                'nombre' => 'Docente CUP',
                'ci' => '9988776',
                'correo' => 'docente@example.com',
                'rol' => Usuario::ROL_DOCENTE,
                'estado' => Usuario::ESTADO_ACTIVO,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contrasena_inicial')
            ->assertRedirect(route('cu02.usuarios.index'));

        $contrasenaInicial = $response->baseResponse->getSession()->get('contrasena_inicial');
        $usuario = Usuario::query()->where('correo', 'docente@example.com')->firstOrFail();

        $this->assertIsString($contrasenaInicial);
        $this->assertMatchesRegularExpression('/^Cup-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $contrasenaInicial);
        $this->assertTrue(Hash::check($contrasenaInicial, $usuario->contrasena_hash));
        $this->assertNotNull($usuario->credenciales_enviadas_en);

        Notification::assertSentTo(
            $usuario,
            EnviarCredencialesIniciales::class,
            fn (EnviarCredencialesIniciales $notification): bool => $notification->correo === 'docente@example.com'
                && $notification->contrasenaInicial === $contrasenaInicial,
        );
    }

    public function test_administrador_puede_registrar_usuario_inactivo_sin_enviar_credenciales(): void
    {
        Notification::fake();

        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->post(route('cu02.usuarios.store'), [
                'nombre' => 'Postulante en espera',
                'ci' => '3344556',
                'correo' => 'postulante@example.com',
                'rol' => Usuario::ROL_POSTULANTE,
                'estado' => Usuario::ESTADO_INACTIVO,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('contrasena_inicial')
            ->assertRedirect(route('cu02.usuarios.index'));

        $usuario = Usuario::query()->where('correo', 'postulante@example.com')->firstOrFail();

        $this->assertSame(Usuario::ESTADO_INACTIVO, $usuario->estado);
        $this->assertNull($usuario->credenciales_enviadas_en);

        Notification::assertNothingSent();
    }

    public function test_activar_usuario_inactivo_envia_credenciales_por_primera_vez(): void
    {
        Notification::fake();

        $administrador = Usuario::factory()->administrador()->create();
        $usuario = Usuario::factory()->inactivo()->create([
            'correo' => 'activacion@example.com',
            'credenciales_enviadas_en' => null,
        ]);

        $response = $this->actingAs($administrador)
            ->patch(route('cu02.usuarios.status.update', $usuario), [
                'estado' => Usuario::ESTADO_ACTIVO,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contrasena_inicial');

        $contrasenaInicial = $response->baseResponse->getSession()->get('contrasena_inicial');
        $usuario->refresh();

        $this->assertIsString($contrasenaInicial);
        $this->assertMatchesRegularExpression('/^Cup-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $contrasenaInicial);
        $this->assertSame(Usuario::ESTADO_ACTIVO, $usuario->estado);
        $this->assertNotNull($usuario->credenciales_enviadas_en);
        $this->assertTrue(Hash::check($contrasenaInicial, $usuario->contrasena_hash));

        Notification::assertSentTo(
            $usuario,
            EnviarCredencialesIniciales::class,
            fn (EnviarCredencialesIniciales $notification): bool => $notification->correo === 'activacion@example.com'
                && $notification->contrasenaInicial === $contrasenaInicial,
        );
    }

    public function test_reactivar_usuario_con_credenciales_enviadas_no_reenvia_correo(): void
    {
        Notification::fake();

        $administrador = Usuario::factory()->administrador()->create();
        $usuario = Usuario::factory()->inactivo()->create([
            'credenciales_enviadas_en' => now(),
        ]);
        $contrasenaHash = $usuario->contrasena_hash;

        $this->actingAs($administrador)
            ->patch(route('cu02.usuarios.status.update', $usuario), [
                'estado' => Usuario::ESTADO_ACTIVO,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('contrasena_inicial');

        $usuario->refresh();

        $this->assertSame(Usuario::ESTADO_ACTIVO, $usuario->estado);
        $this->assertSame($contrasenaHash, $usuario->contrasena_hash);

        Notification::assertNothingSent();
    }

    public function test_administrador_puede_actualizar_usuario(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $usuario = Usuario::factory()->create();

        $this->actingAs($administrador)
            ->put(route('cu02.usuarios.update', $usuario), [
                'nombre' => 'Nombre actualizado',
                'ci' => '7654321',
                'correo' => 'actualizado@example.com',
                'rol' => Usuario::ROL_DOCENTE,
                'estado' => Usuario::ESTADO_INACTIVO,
            ])
            ->assertSessionHasNoErrors();

        $usuario->refresh();

        $this->assertSame('Nombre actualizado', $usuario->nombre);
        $this->assertSame(Usuario::ROL_DOCENTE, $usuario->rol);
        $this->assertSame(Usuario::ESTADO_INACTIVO, $usuario->estado);
    }

    public function test_administrador_puede_restablecer_contrasena_de_usuario(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $usuario = Usuario::factory()->create();

        $this->actingAs($administrador)
            ->put(route('cu02.usuarios.password.update', $usuario), [
                'password' => 'nueva-password',
                'password_confirmation' => 'nueva-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva-password', $usuario->refresh()->contrasena_hash));
    }

    public function test_administrador_puede_cambiar_estado_de_otro_usuario(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $usuario = Usuario::factory()->create();

        $this->actingAs($administrador)
            ->patch(route('cu02.usuarios.status.update', $usuario), [
                'estado' => Usuario::ESTADO_INACTIVO,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Usuario::ESTADO_INACTIVO, $usuario->refresh()->estado);
    }

    public function test_administrador_no_puede_desactivar_su_propia_cuenta(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->patch(route('cu02.usuarios.status.update', $administrador), [
                'estado' => Usuario::ESTADO_INACTIVO,
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame(Usuario::ESTADO_ACTIVO, $administrador->refresh()->estado);
    }

    public function test_no_se_puede_dejar_el_sistema_sin_administrador_activo(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $otroAdministrador = Usuario::factory()->administrador()->inactivo()->create();

        $this->actingAs($administrador)
            ->put(route('cu02.usuarios.update', $administrador), [
                'nombre' => $administrador->nombre,
                'ci' => $administrador->ci,
                'correo' => $administrador->correo,
                'rol' => Usuario::ROL_COORDINADOR,
                'estado' => Usuario::ESTADO_ACTIVO,
            ])
            ->assertSessionHasErrors('rol');

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $administrador->refresh()->rol);
        $this->assertSame(Usuario::ESTADO_INACTIVO, $otroAdministrador->refresh()->estado);
    }
}
