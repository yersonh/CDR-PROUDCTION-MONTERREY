<?php

namespace App\Services;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Exceptions\RecibidoVurDuplicadoException;
use App\Jobs\ValidarCertificadoElectoralConIA;
use App\Models\RecibidoVur;
use App\Models\SolicitudPublica;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecibidoVurService
{
    public function __construct(
        private readonly SolicitudService $solicitudes,
        private readonly DocumentoService $documentos,
    ) {}

    /**
     * Guarda un recibido proveniente de VUR. Idempotente: si ya existe uno
     * para el mismo envío, lanza RecibidoVurDuplicadoException (el
     * Controller la traduce a 409) en vez de crear un duplicado.
     *
     * Deduplicación: si el payload trae referencia_cdr (el id de la
     * SolicitudPublica que originó el envío a VUR), se usa ESO como clave —
     * es más confiable que radicado_vur, que puede chocar con datos viejos
     * de prueba o un reset de numeración en VUR sin ser el mismo envío real.
     * Sin referencia_cdr (recibido originado directamente por VUR, sin pasar
     * por el formulario público de CDR), se deduplica por radicado_vur como
     * siempre se hizo.
     *
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos, UploadedFile $pdf): RecibidoVur
    {
        return DB::transaction(function () use ($datos, $pdf) {
            $existente = filled($datos['referencia_cdr'] ?? null)
                ? RecibidoVur::where('referencia_cdr', $datos['referencia_cdr'])->first()
                : RecibidoVur::where('radicado_vur', $datos['radicado_vur'])->first();

            if ($existente) {
                throw new RecibidoVurDuplicadoException($existente);
            }

            $ruta = $pdf->store('recibidos-vur', 'local');

            return RecibidoVur::create([
                ...$datos,
                'nombre_original_pdf' => $pdf->getClientOriginalName(),
                'ruta_pdf' => $ruta,
                'estado' => 'pendiente',
            ]);
        });
    }

    /**
     * Crea automáticamente la Solicitud a partir de un recibido de VUR, sin
     * intervención manual — para sisben/jac (Funcionario SISBEN / Presidente
     * JAC son quienes validan de fondo) y electoral (validado por IA, ver
     * ValidarCertificadoElectoralConIA — reemplaza el chequeo manual que
     * antes hacía Secretaría con ElectoralForm, ella sigue prevalidando
     * igual después). Casos sin referencia_cdr (el recibido no vino de
     * nuestro formulario público) se quedan `pendiente` para el flujo manual
     * existente ("Crear solicitud").
     *
     * No lanza — cualquier fallo queda logueado y el recibido se queda tal
     * cual estaba (pendiente), disponible para manejo manual.
     */
    public function procesarAutomaticamente(RecibidoVur $recibido): ?Solicitud
    {
        if (! $recibido->referencia_cdr) {
            return null;
        }

        $origen = SolicitudPublica::find($recibido->referencia_cdr);

        if (! $origen || ! in_array($origen->medio_acreditacion, [
            MedioAcreditacion::Sisben, MedioAcreditacion::Jac, MedioAcreditacion::Electoral,
        ], true)) {
            return null;
        }

        try {
            $sistema = User::where('email', 'servicio-vur@sistema.local')->firstOrFail();

            $soporte = $origen->ruta_soporte
                ? $this->comoUploadedFile($origen->ruta_soporte)
                : null;

            $solicitud = $this->solicitudes->radicar(new CreateSolicitudData(
                nombreCompleto: $origen->nombre_completo,
                tipoDocumento: $origen->tipo_documento,
                numeroIdentificacion: $origen->numero_identificacion,
                direccion: $origen->direccion,
                correo: $origen->correo,
                celular: $origen->celular,
                barrioVeredaSector: $origen->barrio_vereda_sector,
                sectorId: $origen->sector_id,
                motivo: $origen->motivo,
                tipoCertificado: $origen->tipo_certificado,
                medioAcreditacion: $origen->medio_acreditacion,
                soporte: $soporte,
                ciudadanoId: null,
                createdBy: $sistema->id,
                radicadoVur: $recibido->radicado_vur,
                recibidoVurId: $recibido->id,
            ));

            $expediente = $solicitud->expediente;

            if ($origen->ruta_documento_identidad) {
                $this->documentos->guardarSubido(
                    $expediente,
                    'documento_identidad',
                    $this->comoUploadedFile($origen->ruta_documento_identidad),
                    $sistema,
                );
            }

            if ($origen->ruta_pdf_firmado) {
                $this->documentos->guardarSubido(
                    $expediente,
                    'solicitud_firmada',
                    $this->comoUploadedFile($origen->ruta_pdf_firmado),
                    $sistema,
                );
            }

            if ($origen->medio_acreditacion === MedioAcreditacion::Electoral) {
                ValidarCertificadoElectoralConIA::dispatch($solicitud->id);
            }

            return $solicitud;
        } catch (\Throwable $e) {
            Log::error("No se pudo auto-procesar el recibido VUR #{$recibido->id}: ".$e->getMessage(), [
                'recibido_id' => $recibido->id,
                'referencia_cdr' => $recibido->referencia_cdr,
            ]);

            return null;
        }
    }

    /**
     * Envuelve un archivo ya guardado en el disco local como UploadedFile,
     * para reusar servicios pensados para uploads reales sin duplicar el
     * archivo físico.
     *
     * OJO: sin pasar un mime explícito, Symfony cae en
     * "application/octet-stream" — DocumentoService lo guarda tal cual, y
     * Gemini rechaza ese mime de plano (ver ValidarCertificadoElectoralConIA)
     * con HTTP 400 "Unsupported MIME type". Por eso se detecta el mime real
     * del archivo en disco en vez de dejarlo en null.
     */
    private function comoUploadedFile(string $rutaRelativa): UploadedFile
    {
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);
        $mime = mime_content_type($rutaAbsoluta) ?: 'application/octet-stream';

        return new UploadedFile($rutaAbsoluta, basename($rutaRelativa), $mime, null, true);
    }
}
