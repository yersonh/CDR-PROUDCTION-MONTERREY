<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Credenciales de acceso para un usuario recién creado por un administrador
 * (Usuario o Presidente JAC): la contraseña la genera el sistema, nunca la
 * escribe quien crea la cuenta. El usuario debe cambiarla al ingresar (ver
 * User::must_change_password + AuthController::changePassword).
 */
class CredencialesTemporalesNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $passwordTemporal,
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
        $url = rtrim(config('app.frontend_url', ''), '/').'/login';

        return (new MailMessage)
            ->subject('Su acceso al Certificado de Residencia Digital')
            ->greeting("Hola {$this->user->name},")
            ->line('Se ha creado una cuenta para usted en el sistema de Certificado de Residencia Digital.')
            ->line("Correo de acceso: {$this->user->email}")
            ->line("Contraseña temporal: {$this->passwordTemporal}")
            ->line('Por seguridad, debe cambiarla en las próximas 24 horas la primera vez que ingrese.')
            ->action('Iniciar sesión', $url)
            ->line('Si usted no esperaba este correo, contacte al administrador del sistema.')
            ->salutation('Alcaldía de Monterrey · Casanare');
    }
}
