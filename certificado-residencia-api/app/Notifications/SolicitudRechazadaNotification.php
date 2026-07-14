<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno (correo) al Alcalde cuando Secretaría rechaza una solicitud
 * en la prevalidación — solo a título informativo/supervisión, el Alcalde no
 * tiene que actuar sobre ella (queda en estado terminal).
 */
class SolicitudRechazadaNotification extends Notification
{
    use Queueable;

    public function __construct(public Solicitud $solicitud, public ?string $observacion) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->solicitud;
        $url = rtrim(config('app.frontend_url', ''), '/')."/solicitudes/{$s->id}";

        $mensaje = (new MailMessage)
            ->subject("Solicitud rechazada · {$s->radicado}")
            ->greeting("Hola {$notifiable->name},")
            ->line("La solicitud {$s->radicado} de {$s->nombre_completo} fue rechazada en la prevalidación.");

        if ($this->observacion) {
            $mensaje->line("Motivo: {$this->observacion}");
        }

        return $mensaje
            ->action('Ver solicitud', $url)
            ->salutation('Certificado de Residencia Digital');
    }
}
