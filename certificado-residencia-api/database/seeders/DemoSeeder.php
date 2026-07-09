<?php

namespace Database\Seeders;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\ResultadoValidacion;
use App\Enums\TipoCertificado;
use App\Models\User;
use App\Services\CertificadoService;
use App\Services\SolicitudService;
use App\Models\Solicitud;
use App\Services\ValidacionService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Datos de demostración: solicitudes en distintos estados del flujo,
 * para poblar dashboards y bandejas. Ejecutar tras UserSeeder.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotente: si ya existe alguna solicitud demo (por su número de
        // identificación fijo), no vuelve a crearlas. Necesario porque este
        // seeder se ejecuta en cada despliegue (ver docker/entrypoint.sh).
        if (Solicitud::where('numero_identificacion', '43111222')->exists()) {
            return;
        }

        $ciudadano = User::role('ciudadano')->first();
        $operador = User::role('operador')->first();
        $alcalde = User::role('alcalde')->first();

        if (! $ciudadano || ! $operador || ! $alcalde) {
            return;
        }

        $solicitudes = app(SolicitudService::class);
        $validaciones = app(ValidacionService::class);
        $certificados = app(CertificadoService::class);

        $muestras = [
            ['Laura Giraldo', '43111222', 'Barrio El Prado', MedioAcreditacion::Electoral, 'radicada'],
            ['Carlos Mesa', '43222333', 'Vereda La Aurora', MedioAcreditacion::Sisben, 'radicada'],
            ['Diana Rojas', '43333444', 'Barrio Centro', MedioAcreditacion::Electoral, 'preaprobada'],
            ['Andrés Cano', '43444555', 'Barrio El Prado', MedioAcreditacion::Especial, 'certificada'],
            ['Paola Nieto', '43555666', 'Vereda San Luis', MedioAcreditacion::Electoral, 'pendiente_soporte'],
            ['Jorge Vega', '43666777', 'Barrio Centro', MedioAcreditacion::Jac, 'radicada'],
        ];

        foreach ($muestras as [$nombre, $doc, $sector, $medio, $estadoFinal]) {
            $soporte = $medio === MedioAcreditacion::Especial
                ? null
                : UploadedFile::fake()->create('soporte.pdf', 40, 'application/pdf');

            $solicitud = $solicitudes->radicar(new CreateSolicitudData(
                nombreCompleto: $nombre,
                tipoDocumento: 'CC',
                numeroIdentificacion: $doc,
                direccion: 'Dirección '.$doc,
                correo: strtolower(explode(' ', $nombre)[0]).'@example.com',
                celular: '31000'.$doc,
                barrioVeredaSector: $sector,
                motivo: 'Trámite de demostración',
                tipoCertificado: TipoCertificado::General,
                medioAcreditacion: $medio,
                justificacionEspecial: $medio === MedioAcreditacion::Especial ? 'Caso especial de demostración' : null,
                soporte: $soporte,
                ciudadanoId: $ciudadano->id,
                createdBy: $ciudadano->id,
            ));

            if (in_array($estadoFinal, ['preaprobada', 'certificada', 'pendiente_soporte'], true)) {
                $validaciones->registrarSoporte($solicitud, $medio->value, null, null, ResultadoValidacion::Cumple, 'Validado (demo)', $operador);

                $resultado = $estadoFinal === 'pendiente_soporte' ? ResultadoValidacion::Subsanar : ResultadoValidacion::Cumple;
                $validaciones->prevalidar($solicitud, $resultado, $estadoFinal === 'pendiente_soporte' ? 'Soporte ilegible (demo)' : null, $operador);

                if ($estadoFinal === 'certificada') {
                    $certificados->firmar($solicitud->refresh(), $alcalde);
                }
            }
        }
    }
}
