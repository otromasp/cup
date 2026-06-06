<?php

namespace App\Notifications\CasosUso\CU01GestionarAcceso;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnviarTokenRecuperacionContrasena extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $correo,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $this->correo,
        ]);

        return (new MailMessage)
            ->subject('Recuperacion de contrasena - Sistema CUP')
            ->line('Recibimos una solicitud para restablecer tu contrasena.')
            ->action('Restablecer contrasena', $url)
            ->line('Si no solicitaste este cambio, puedes ignorar este correo.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
