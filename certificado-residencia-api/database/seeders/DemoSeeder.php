<?php

namespace Database\Seeders;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\ResultadoValidacion;
use App\Enums\TipoCertificado;
use App\Models\PresidenteJac;
use App\Models\Sector;
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
        // El usuario demo "Presidente JAC" (ver UserSeeder) necesita estar
        // atado a un sector para poder certificar algo — cada presidente
        // ahora solo ve/certifica lo de su propio sector. Va ANTES del
        // guard de idempotencia de abajo: en despliegues que ya tenían
        // datos demo previos a que existiera PresidenteJac, ese guard
        // devolvería antes de llegar aquí y este vínculo nunca se crearía.
        $barrioCentro = Sector::where('nombre', 'Barrio Centro')->first();
        $jac = User::role('presidente_jac')->first();

        if ($barrioCentro && $jac && ! PresidenteJac::where('user_id', $jac->id)->exists()) {
            PresidenteJac::create([
                'sector_id' => $barrioCentro->id,
                'nombre_completo' => $jac->name,
                'tipo_documento' => 'CC',
                'numero_identificacion' => '43000111',
                'direccion' => 'Dirección Presidente JAC (demo)',
                'celular' => '3100000000',
                'correo' => $jac->email,
                'fecha_inicio_periodo' => now()->subYear()->toDateString(),
                'estado' => 'activo',
                'user_id' => $jac->id,
            ]);
        }

        // Idempotente: si ya existe alguna solicitud demo (por su número de
        // identificación fijo), no vuelve a crearlas. Necesario porque este
        // seeder se ejecuta en cada despliegue (ver docker/entrypoint.sh).
        if (Solicitud::where('numero_identificacion', '43111222')->exists()) {
            return;
        }

        $secretaria = User::role('secretaria')->first();
        $alcalde = User::role('alcalde')->first();

        if (! $secretaria || ! $alcalde) {
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
                sectorId: Sector::where('nombre', $sector)->value('id'),
                motivo: 'Trámite de demostración',
                tipoCertificado: TipoCertificado::General,
                medioAcreditacion: $medio,
                justificacionEspecial: $medio === MedioAcreditacion::Especial ? 'Caso especial de demostración' : null,
                soporte: $soporte,
                ciudadanoId: null,
                createdBy: $secretaria->id,
            ));

            if (in_array($estadoFinal, ['preaprobada', 'certificada', 'pendiente_soporte'], true)) {
                $validaciones->registrarSoporte($solicitud, $medio->value, null, null, ResultadoValidacion::Cumple, 'Validado (demo)', $secretaria);

                $resultado = $estadoFinal === 'pendiente_soporte' ? ResultadoValidacion::Subsanar : ResultadoValidacion::Cumple;
                $validaciones->prevalidar($solicitud, $resultado, $estadoFinal === 'pendiente_soporte' ? 'Soporte ilegible (demo)' : null, $secretaria);

                if ($estadoFinal === 'certificada') {
                    $certificados->firmar($solicitud->refresh(), $alcalde);
                }
            }
        }
    }
}
