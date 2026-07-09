<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\ExpedienteDocumento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gestión centralizada del almacenamiento de documentos del expediente,
 * con versionamiento: al cargar un documento del mismo tipo, la versión
 * anterior se marca como no vigente y se incrementa el consecutivo.
 */
class DocumentoService
{
    public function __construct(private readonly AuditService $audit) {}

    /** Guarda un archivo subido (UploadedFile) versionando el tipo. */
    public function guardarSubido(
        Expediente $expediente,
        string $tipo,
        UploadedFile $file,
        User $actor,
        bool $esCertificado = false,
    ): ExpedienteDocumento {
        $path = $file->store("expedientes/{$expediente->codigo}", 'local');

        return $this->registrar($expediente, $tipo, [
            'nombre_original' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', Storage::disk('local')->path($path)),
        ], $actor, $esCertificado);
    }

    /** Guarda un documento a partir de bytes en memoria (p. ej. PDF generado). */
    public function guardarBytes(
        Expediente $expediente,
        string $tipo,
        string $contenido,
        string $nombre,
        string $mime,
        User $actor,
        bool $esCertificado = false,
        ?string $pathFijo = null,
    ): ExpedienteDocumento {
        $path = $pathFijo ?? "expedientes/{$expediente->codigo}/{$nombre}";
        Storage::disk('local')->put($path, $contenido);

        return $this->registrar($expediente, $tipo, [
            'nombre_original' => $nombre,
            'path' => $path,
            'mime' => $mime,
            'size' => strlen($contenido),
            'hash' => hash('sha256', $contenido),
        ], $actor, $esCertificado);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function registrar(Expediente $expediente, string $tipo, array $datos, User $actor, bool $esCertificado): ExpedienteDocumento
    {
        // Versionar: marcar como no vigentes los documentos anteriores del mismo tipo
        $anterior = $expediente->documentos()
            ->where('tipo', $tipo)
            ->where('vigente', true)
            ->orderByDesc('version')
            ->first();

        if ($anterior) {
            $expediente->documentos()->where('tipo', $tipo)->update(['vigente' => false]);
        }

        $documento = $expediente->documentos()->create([
            ...$datos,
            'tipo' => $tipo,
            'disk' => 'local',
            'es_certificado' => $esCertificado,
            'version' => $anterior ? $anterior->version + 1 : 1,
            'vigente' => true,
            'reemplaza_a' => $anterior?->id,
            'subido_por' => $actor->id,
        ]);

        if ($anterior) {
            $this->audit->registrar(
                accion: 'documento.versionado',
                auditable: $documento,
                descripcion: "Nueva versión ({$documento->version}) del documento {$tipo}",
                actor: $actor,
            );
        }

        return $documento;
    }
}
