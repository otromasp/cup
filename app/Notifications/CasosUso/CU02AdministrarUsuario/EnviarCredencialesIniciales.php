<?php

namespace App\Notifications\CasosUso\CU02AdministrarUsuario;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnviarCredencialesIniciales extends Notification
{
    use Queueable;

    public function __construct(
        public string $correo,
        public string $contrasenaInicial,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Credenciales de acceso - Sistema CUP')
            ->line('Tu cuenta del Sistema CUP ya se encuentra habilitada.')
            ->line('Usuario: '.$this->correo)
            ->line('Contrasena: '.$this->contrasenaInicial)
            ->action('Ingresar al sistema', route('login'))
            ->line('Puedes conservar esta contrasena o cambiarla desde tu perfil cuando ingreses.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
