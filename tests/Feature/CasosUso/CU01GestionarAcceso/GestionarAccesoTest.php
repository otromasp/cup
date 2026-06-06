<?php

namespace Tests\Feature\CasosUso\CU01GestionarAcceso;

use App\Models\TokenRecuperacionContrasena;
use App\Models\Usuario;
use App\Notifications\CasosUso\CU01GestionarAcceso\EnviarTokenRecuperacionContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GestionarAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_activo_puede_iniciar_sesion()
    {
        $usuario = Usuario::factory()->create();

        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($usuario);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion()
    {
        $usuario = Usuario::factory()->create([
            'estado' => 'inactivo',
        ]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_solicitar_recuperacion_genera_token_y_notifica_por_correo()
    {
        Notification::fake();

        $usuario = Usuario::factory()->create();

        $this->post('/forgot-password', ['email' => $usuario->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tokens_recuperacion_contrasena', [
            'usuario_id' => $usuario->getKey(),
            'estado' => TokenRecuperacionContrasena::ESTADO_VIGENTE,
        ]);

        Notification::assertSentTo($usuario, EnviarTokenRecuperacionContrasena::class);
    }

    public function test_solicitar_recuperacion_no_revela_correos_inexistentes()
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nadie@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('tokens_recuperacion_contrasena', 0);
        Notification::assertNothingSent();
    }

    public function test_token_valido_restablece_contrasena_y_queda_usado()
    {
        Notification::fake();

        $usuario = Usuario::factory()->create();

        $this->post('/forgot-password', ['email' => $usuario->email]);

        Notification::assertSentTo(
            $usuario,
            EnviarTokenRecuperacionContrasena::class,
            function (EnviarTokenRecuperacionContrasena $notification) use ($usuario): bool {
                $response = $this->post('/reset-password', [
                    'token' => $notification->token,
                    'email' => $usuario->email,
                    'password' => 'nueva-password',
                    'password_confirmation' => 'nueva-password',
                ]);

                $response
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('login'));

                $usuario->refresh();

                $this->assertTrue(Hash::check('nueva-password', $usuario->password));
                $this->assertDatabaseHas('tokens_recuperacion_contrasena', [
                    'usuario_id' => $usuario->getKey(),
                    'estado' => TokenRecuperacionContrasena::ESTADO_USADO,
                ]);

                return true;
            }
        );
    }

    public function test_token_invalido_no_restablece_contrasena()
    {
        $usuario = Usuario::factory()->create();

        $this->post('/reset-password', [
            'token' => 'token-invalido',
            'email' => $usuario->email,
            'password' => 'nueva-password',
            'password_confirmation' => 'nueva-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $usuario->refresh()->password));
    }
}
