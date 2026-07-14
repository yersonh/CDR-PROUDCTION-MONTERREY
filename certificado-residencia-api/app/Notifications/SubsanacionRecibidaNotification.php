<?php

namespace App\Notifications;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno (correo) a Secretaría cuando el ciudadano responde una
 * subsanación por el enlace público y vuelve a subir lo pedido.
 */
class SubsanacionRecibidaNotification extends Notification
{
    use Queueable;

    public function __construct(public Solicitud $solicitud, public string $tipoDocumentoLabel) {}

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

        return (new MailMessage)
            ->subject("Subsanación recibida · {$s->radicado}")
            ->greeting("Hola {$notifiable->name},")
            ->line("El ciudadano {$s->nombre_completo} respondió la subsanación solicitada de la solicitud {$s->radicado} y cargó: {$this->tipoDocumentoLabel}.")
            ->action('Revisar solicitud', $url)
            ->salutation('Certificado de Residencia Digital');
    }
}
