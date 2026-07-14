<?php

namespace App\Services;

use App\Enums\EstadoCertificado;
use App\Enums\EstadoSolicitud;
use App\Enums\ResultadoValidacion;
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

    /** Día del mes en letras, como se estila en la redacción de actos administrativos ("primero", no "uno"). */
    private const DIAS_EN_LETRAS = [
        1 => 'primero', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco', 6 => 'seis', 7 => 'siete',
        8 => 'ocho', 9 => 'nueve', 10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce',
        15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
        20 => 'veinte', 21 => 'veintiuno', 22 => 'veintidós', 23 => 'veintitrés', 24 => 'veinticuatro',
        25 => 'veinticinco', 26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
        30 => 'treinta', 31 => 'treinta y uno',
    ];

    private const CANTIDAD_MESES_EN_LETRAS = [
        1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco', 6 => 'seis',
        7 => 'siete', 8 => 'ocho', 9 => 'nueve', 10 => 'diez', 11 => 'once', 12 => 'doce',
    ];

    private const TIPOS_DOCUMENTO = [
        'CC' => 'cédula de ciudadanía', 'TI' => 'tarjeta de identidad', 'CE' => 'cédula de extranjería',
        'PA' => 'pasaporte', 'PEP' => 'permiso especial de permanencia', 'NIT' => 'NIT',
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

        if (! $alcalde->firma_path || ! Storage::disk('local')->exists($alcalde->firma_path)) {
            throw new RuntimeException(
                'No tiene firma electrónica registrada. Debe cargarla en Mi perfil antes de firmar certificados.',
            );
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

        // Imagen de firma del Alcalde, si la tiene cargada. Se reduce a un
        // tamaño fijo en servidor (no solo con CSS): DomPDF calcula el alto
        // de las filas de tabla a partir del tamaño intrínseco de la imagen
        // ANTES de aplicar max-height, así que una firma subida a resolución
        // real (p. ej. 1887×906) hace que la tabla "no quepa" en el espacio
        // restante de la página y empuje todo el bloque de firma a una
        // segunda hoja en blanco, aunque visualmente sobre espacio.
        $firmaImg = '';
        $firmaPath = $certificado->firmadoPor?->firma_path;
        if ($firmaPath && Storage::disk('local')->exists($firmaPath)) {
            $firmaImg = $this->miniaturaBase64(Storage::disk('local')->get($firmaPath), 220, 90);
        }

        // Quien "proyectó" el certificado: la Secretaría que prevalidó con
        // concepto "Cumple" (ver ValidacionService::prevalidar, que exige
        // firma cargada antes de poder emitir ese concepto).
        $prevalidacion = $s->validaciones()
            ->where('tipo', 'prevalidacion')
            ->where('resultado', ResultadoValidacion::Cumple->value)
            ->with('validadoPor')
            ->latest('validado_at')
            ->first();

        $proyectoImg = '';
        $proyectoPath = $prevalidacion?->validadoPor?->firma_path;
        if ($proyectoPath && Storage::disk('local')->exists($proyectoPath)) {
            $proyectoImg = $this->miniaturaBase64(Storage::disk('local')->get($proyectoPath), 220, 90);
        }

        // No se usa diffInMonths() entre las fechas reales: al no tener todos
        // los meses la misma duración, da valores como 2.9 en vez de 3 y
        // redondea mal. Se deriva directamente de la constante de vigencia.
        $mesesVigencia = (int) round(self::VIGENCIA_DIAS / 30);

        return Pdf::loadView('certificados.certificado', [
            'certificado' => $certificado,
            's' => $s,
            'qr' => $qr,
            'escudo' => $escudo,
            'firma_img' => $firmaImg,
            'proyecto_img' => $proyectoImg,
            'proyecto_nombre' => $prevalidacion?->validadoPor?->name,
            'verificacion_url' => $verificacionUrl,
            'meses' => self::MESES,
            'dia_letras' => self::DIAS_EN_LETRAS[(int) $certificado->fecha_expedicion->format('j')] ?? $certificado->fecha_expedicion->format('d'),
            'meses_vigencia' => $mesesVigencia,
            'meses_vigencia_letras' => self::CANTIDAD_MESES_EN_LETRAS[$mesesVigencia] ?? (string) $mesesVigencia,
            'tipo_documento_label' => self::TIPOS_DOCUMENTO[$s->tipo_documento] ?? ($s->tipo_documento ?? 'documento de identidad'),
        ])->setPaper('letter')->output();
    }

    /**
     * Redimensiona una imagen (manteniendo proporción, sin recortar) a un
     * tamaño máximo en píxeles y la devuelve como data URI PNG. Si GD no
     * está disponible o la imagen no se puede leer, devuelve el binario
     * original tal cual (degrada con gracia, no bloquea la generación).
     */
    private function miniaturaBase64(string $binario, int $maxAncho, int $maxAlto): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return 'data:image/png;base64,'.base64_encode($binario);
        }

        $origen = @imagecreatefromstring($binario);
        if (! $origen) {
            return 'data:image/png;base64,'.base64_encode($binario);
        }

        $anchoOrigen = imagesx($origen);
        $altoOrigen = imagesy($origen);
        $escala = min($maxAncho / $anchoOrigen, $maxAlto / $altoOrigen, 1);
        $ancho = max(1, (int) round($anchoOrigen * $escala));
        $alto = max(1, (int) round($altoOrigen * $escala));

        $miniatura = imagecreatetruecolor($ancho, $alto);
        imagealphablending($miniatura, false);
        imagesavealpha($miniatura, true);
        imagecopyresampled($miniatura, $origen, 0, 0, 0, 0, $ancho, $alto, $anchoOrigen, $altoOrigen);
        imagedestroy($origen);

        ob_start();
        imagepng($miniatura);
        $contenido = ob_get_clean();
        imagedestroy($miniatura);

        return 'data:image/png;base64,'.base64_encode($contenido);
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
