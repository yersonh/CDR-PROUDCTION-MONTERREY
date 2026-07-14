<?php

namespace App\Notifications;

use App\Enums\ResultadoValidacion;
use App\Models\Solicitud;
use App\Support\TipoDocumentoCatalogo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Aviso al ciudadano cuando se registra un concepto sobre su solicitud:
 * SISBEN, JAC (ValidacionService::registrarSoporte) o Secretaría
 * (ValidacionService::prevalidar). Un solo aviso, positivo o negativo.
 */
class ConceptoRegistradoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Solicitud $solicitud,
        public string $origen,
        public ResultadoValidacion $resultado,
        public ?string $observacion,
        public ?string $tipoDocumento = null,
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
        $s = $this->solicitud;

        $quien = match ($this->origen) {
            'sisben' => 'el Funcionario SISBEN',
            'jac' => 'el Presidente de la JAC',
            default => 'Secretaría',
        };

        $mensaje = new MailMessage;
        $mensaje->subject("Actualización de su trámite {$s->radicado}")
            ->greeting("Hola {$s->nombre_completo},")
            ->line("Le informamos que {$quien} registró un concepto sobre su Certificado de Residencia con radicado {$s->radicado}.")
            ->line("Resultado: {$this->resultado->label()}");

        if ($this->observacion) {
            $mensaje->line("Observación: {$this->observacion}");
        }

        match ($this->resultado) {
            ResultadoValidacion::Cumple => $mensaje->line('Su trámite continúa su curso normal.'),
            ResultadoValidacion::Subsanar => $mensaje
                ->line($this->tipoDocumento
                    ? 'Debe corregir y volver a cargar el siguiente documento: '.TipoDocumentoCatalogo::label($this->tipoDocumento).'.'
                    : 'Para continuar con su trámite, debe corregir y volver a enviar lo solicitado.')
                ->action('Corregir mi solicitud', $this->enlacePublico())
                ->line('El enlace es personal, vence en 30 días y no requiere crear una cuenta.'),
            ResultadoValidacion::Rechaza => $mensaje
                ->line('Para más información sobre los próximos pasos, comuníquese con la Alcaldía indicando el número de radicado.'),
        };

        return $mensaje->salutation('Alcaldía de Monterrey · Casanare');
    }

    /**
     * Enlace público firmado (sin login) a la vista donde el ciudadano puede
     * volver a cargar el soporte solicitado. La firma cubre la ruta de la
     * API; el enlace del correo apunta al frontend con la misma consulta
     * firmada, que el frontend reenvía tal cual al llamar a la API.
     */
    private function enlacePublico(): string
    {
        $urlFirmada = URL::temporarySignedRoute(
            'public.subsanacion.show',
            now()->addDays(30),
            ['solicitud' => $this->solicitud->id],
        );

        $query = parse_url($urlFirmada, PHP_URL_QUERY);
        $frontend = rtrim(config('app.frontend_url', ''), '/');

        return "{$frontend}/corregir/{$this->solicitud->id}?{$query}";
    }
}
