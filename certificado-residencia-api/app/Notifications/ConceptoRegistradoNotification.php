<?php

namespace App\Notifications;

use App\Enums\ResultadoValidacion;
use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $positivo = $this->resultado === ResultadoValidacion::Cumple;

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

        if ($positivo) {
            $mensaje->line('Su trámite continúa su curso normal.');
        } else {
            $mensaje->line('Puede consultar el detalle y los próximos pasos ingresando a su cuenta.');
        }

        return $mensaje->salutation('Alcaldía de Monterrey · Casanare');
    }
}
