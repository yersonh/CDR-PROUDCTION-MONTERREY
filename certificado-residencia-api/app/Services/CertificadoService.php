<?php

namespace App\Services;

use App\Enums\EstadoCertificado;
use App\Enums\EstadoSolicitud;
use App\Models\Certificado;
use App\Models\Solicitud;
use App\Models\User;
use App\Notifications\CertificadoEmitidoNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CertificadoService
{
    /** Vigencia del certificado de residencia (días calendario). */
    public const VIGENCIA_DIAS = 90;

    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function __construct(
        private readonly RadicadoGenerator $consecutivos,
        private readonly QrService $qr,
        private readonly SolicitudService $solicitudes,
        private readonly AuditService $audit,
        private readonly DocumentoService $documentos,
    ) {}

    /**
     * Firma y genera el certificado oficial de una solicitud preaprobada.
     */
    public function firmar(Solicitud $solicitud, User $alcalde): Certificado
    {
        if ($solicitud->estado !== EstadoSolicitud::Preaprobada) {
            throw new RuntimeException("La solicitud {$solicitud->radicado} no está en estado Preaprobada.");
        }

        // Paso a "En firma"
        $this->solicitudes->cambiarEstado($solicitud, EstadoSolicitud::EnFirma, 'Firma iniciada por el Alcalde.', $alcalde);

        $certificado = DB::transaction(function () use ($solicitud, $alcalde) {
            $ahora = now();

            $certificado = $solicitud->certificado()->create([
                'consecutivo' => $this->consecutivos->nuevoCertificado((int) $ahora->year),
                'codigo_verificacion' => $this->codigoVerificacionUnico(),
                'firmado_por' => $alcalde->id,
                'fecha_expedicion' => $ahora,
                'vigencia_hasta' => $ahora->copy()->addDays(self::VIGENCIA_DIAS),
                'estado' => EstadoCertificado::Vigente,
            ]);

            $pdf = $this->renderPdf($certificado->load('solicitud', 'firmadoPor'));

            // Almacenar el PDF y registrar hash de integridad
            $expediente = $solicitud->expediente()->firstOrFail();
            $path = "expedientes/{$expediente->codigo}/certificado_{$certificado->consecutivo}.pdf";

            $this->documentos->guardarBytes(
                expediente: $expediente,
                tipo: 'certificado_final',
                contenido: $pdf,
                nombre: "certificado_{$certificado->consecutivo}.pdf",
                mime: 'application/pdf',
                actor: $alcalde,
                esCertificado: true,
                pathFijo: $path,
            );

            $certificado->update([
                'pdf_path' => $path,
                'hash_documento' => hash('sha256', $pdf),
            ]);

            $this->audit->registrar(
                accion: 'certificado.emitido',
                auditable: $certificado,
                descripcion: "Certificado {$certificado->consecutivo} firmado y expedido",
                despues: ['consecutivo' => $certificado->consecutivo, 'hash' => $certificado->hash_documento],
                actor: $alcalde,
            );

            return $certificado;
        });

        // Paso a "Certificada" + entrega automática
        $this->solicitudes->cambiarEstado(
            $solicitud,
            EstadoSolicitud::Certificada,
            "Certificado {$certificado->consecutivo} expedido y entregado.",
            $alcalde,
        );

        $this->notificar($certificado);

        return $certificado->refresh();
    }

    /**
     * Firma masiva de varias solicitudes preaprobadas.
     *
     * @param  iterable<Solicitud>  $solicitudes
     * @return array{firmadas: list<string>, errores: array<string, string>}
     */
    public function firmarLote(iterable $solicitudes, User $alcalde): array
    {
        $firmadas = [];
        $errores = [];

        foreach ($solicitudes as $solicitud) {
            try {
                $certificado = $this->firmar($solicitud, $alcalde);
                $firmadas[] = $certificado->consecutivo;
            } catch (\Throwable $e) {
                $errores[$solicitud->radicado] = $e->getMessage();
            }
        }

        return ['firmadas' => $firmadas, 'errores' => $errores];
    }

    /** Renderiza el PDF del certificado como binario. */
    public function renderPdf(Certificado $certificado): string
    {
        $s = $certificado->solicitud;
        $verificacionUrl = rtrim(config('app.frontend_url', ''), '/').'/verificar';
        $qr = $this->qr->dataUri($verificacionUrl."?codigo={$certificado->codigo_verificacion}");

        // El escudo vive en el código (resources), no en el volumen de storage
        $escudoPath = resource_path('branding/escudo.png');
        $escudo = is_file($escudoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($escudoPath))
            : '';

        // Imagen de firma del Alcalde, si la tiene cargada
        $firmaImg = '';
        $firmaPath = $certificado->firmadoPor?->firma_path;
        if ($firmaPath && Storage::disk('local')->exists($firmaPath)) {
            $firmaImg = 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($firmaPath));
        }

        return Pdf::loadView('certificados.certificado', [
            'certificado' => $certificado,
            's' => $s,
            'qr' => $qr,
            'escudo' => $escudo,
            'firma_img' => $firmaImg,
            'verificacion_url' => $verificacionUrl,
            'meses' => self::MESES,
        ])->setPaper('letter')->output();
    }

    private function codigoVerificacionUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(4).'-'.Str::random(4));
        } while (Certificado::where('codigo_verificacion', $codigo)->exists());

        return $codigo;
    }

    private function notificar(Certificado $certificado): void
    {
        try {
            Notification::route('mail', $certificado->solicitud->correo)
                ->notify(new CertificadoEmitidoNotification($certificado));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
