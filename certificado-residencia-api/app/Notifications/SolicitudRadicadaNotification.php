<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudRadicadaNotification extends Notification
{
    use Queueable;

    public function __construct(public Solicitud $solicitud) {}

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
        $url = rtrim(config('app.frontend_url', ''), '/')."/consulta?radicado={$s->radicado}";

        return (new MailMessage)
            ->subject("Radicación exitosa · {$s->radicado}")
            ->greeting("Hola {$s->nombre_completo},")
            ->line('Su solicitud de Certificado de Residencia ha sido radicada correctamente.')
            ->line("Número de radicación: {$s->radicado}")
            ->line('Fecha de presentación: '.$s->fecha_radicacion->format('d/m/Y H:i'))
            ->line('Estado inicial del trámite: Radicada')
            ->line('Fecha límite de respuesta: '.$s->fecha_limite_sla?->format('d/m/Y'))
            ->action('Consultar seguimiento', $url)
            ->line('Recibirá notificaciones a medida que avance su trámite.')
            ->salutation('Alcaldía de Monterrey · Casanare');
    }
}
