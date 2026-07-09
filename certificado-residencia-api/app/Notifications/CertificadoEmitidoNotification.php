<?php

namespace App\Notifications;

use App\Models\Certificado;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificadoEmitidoNotification extends Notification
{
    use Queueable;

    public function __construct(public Certificado $certificado) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->certificado;
        $s = $c->solicitud;
        $url = rtrim(config('app.frontend_url', ''), '/')."/verificar?codigo={$c->codigo_verificacion}";

        return (new MailMessage)
            ->subject("Certificado de Residencia expedido · {$c->consecutivo}")
            ->greeting("Hola {$s->nombre_completo},")
            ->line('Su Certificado de Residencia ha sido firmado y expedido oficialmente.')
            ->line("Número de certificado: {$c->consecutivo}")
            ->line("Código de verificación: {$c->codigo_verificacion}")
            ->line('Vigencia hasta: '.$c->vigencia_hasta->format('d/m/Y'))
            ->action('Verificar y descargar', $url)
            ->line('Puede verificar la autenticidad en cualquier momento con el código o el QR del documento.')
            ->salutation('Alcaldía de Monterrey · Casanare');
    }
}
