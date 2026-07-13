<?php

namespace Tests\Feature;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Jobs\ValidarCertificadoElectoralConIA;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\SolicitudService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ValidarCertificadoElectoralConIATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class]);
        Storage::fake('local');
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);

        User::factory()->create(['email' => 'ia-electoral@sistema.local', 'activo' => true]);
    }

    private function radicarElectoral(): Solicitud
    {
        $secretaria = User::factory()->create(['password' => Hash::make('password'), 'activo' => true]);
        $secretaria->assignRole('secretaria');

        $data = new CreateSolicitudData(
            nombreCompleto: 'Carlos Mesa',
            tipoDocumento: 'CC',
            numeroIdentificacion: '43222333',
            direccion: 'x',
            correo: 'c@c.com',
            celular: '3001112233',
            barrioVeredaSector: 'y',
            motivo: null,
            tipoCertificado: TipoCertificado::General,
            medioAcreditacion: MedioAcreditacion::Electoral,
            justificacionEspecial: null,
            soporte: UploadedFile::fake()->create('certificado_electoral.pdf', 20, 'application/pdf'),
            createdBy: $secretaria->id,
        );

        return app(SolicitudService::class)->radicar($data);
    }

    private function fakeGemini(bool $valido, string $motivo = 'ok'): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['valido' => $valido, 'motivo' => $motivo]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);
    }

    public function test_ia_aprueba_certificado_valido_y_registra_cumple(): void
    {
        $solicitud = $this->radicarElectoral();
        $this->fakeGemini(true, 'Certificado electoral vigente y legible');

        (new ValidarCertificadoElectoralConIA($solicitud->id))->handle(app(\App\Services\GeminiService::class), app(\App\Services\ValidacionService::class));

        $validacion = $solicitud->fresh()->validaciones()->where('tipo', 'electoral')->first();
        $this->assertNotNull($validacion);
        $this->assertSame('cumple', $validacion->resultado->value);
        $this->assertStringContainsString('Gemini', $validacion->observacion);
        $this->assertSame('en_validacion', $solicitud->fresh()->estado->value);
    }

    public function test_ia_rechaza_certificado_invalido_y_registra_rechaza(): void
    {
        $solicitud = $this->radicarElectoral();
        $this->fakeGemini(false, 'El documento no corresponde a un certificado electoral vigente');

        (new ValidarCertificadoElectoralConIA($solicitud->id))->handle(app(\App\Services\GeminiService::class), app(\App\Services\ValidacionService::class));

        $validacion = $solicitud->fresh()->validaciones()->where('tipo', 'electoral')->first();
        $this->assertSame('rechaza', $validacion->resultado->value);
    }

    public function test_ia_notifica_a_secretaria_tras_validar(): void
    {
        $secretaria = User::factory()->create(['activo' => true]);
        $secretaria->assignRole('secretaria');

        $solicitud = $this->radicarElectoral();
        \App\Models\Notificacion::query()->delete(); // limpiar la de radicar()

        $this->fakeGemini(true);
        (new ValidarCertificadoElectoralConIA($solicitud->id))->handle(app(\App\Services\GeminiService::class), app(\App\Services\ValidacionService::class));

        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $secretaria->id,
            'solicitud_id' => $solicitud->id,
        ]);
    }

    public function test_no_revalida_si_ya_existe_una_validacion_electoral(): void
    {
        $solicitud = $this->radicarElectoral();
        $this->fakeGemini(true);

        // Ya fue validada (p. ej. a mano por Secretaría) antes de que corra el job.
        $sistema = User::where('email', 'ia-electoral@sistema.local')->first();
        app(\App\Services\ValidacionService::class)->registrarSoporte(
            $solicitud, 'electoral', null, null, \App\Enums\ResultadoValidacion::Cumple, 'manual', $sistema,
        );

        (new ValidarCertificadoElectoralConIA($solicitud->id))->handle(app(\App\Services\GeminiService::class), app(\App\Services\ValidacionService::class));

        $this->assertSame(1, $solicitud->fresh()->validaciones()->where('tipo', 'electoral')->count());
        Http::assertNothingSent();
    }

    public function test_sin_documento_soporte_no_lanza_y_deja_para_validacion_manual(): void
    {
        $secretaria = User::factory()->create(['password' => Hash::make('password'), 'activo' => true]);
        $secretaria->assignRole('secretaria');

        $data = new CreateSolicitudData(
            nombreCompleto: 'Sin Soporte',
            tipoDocumento: 'CC',
            numeroIdentificacion: '000111',
            direccion: 'x',
            correo: 's@s.com',
            celular: '3001112233',
            barrioVeredaSector: 'y',
            motivo: null,
            tipoCertificado: TipoCertificado::General,
            medioAcreditacion: MedioAcreditacion::Electoral,
            justificacionEspecial: null,
            soporte: null,
            createdBy: $secretaria->id,
        );
        $solicitud = app(SolicitudService::class)->radicar($data);

        (new ValidarCertificadoElectoralConIA($solicitud->id))->handle(app(\App\Services\GeminiService::class), app(\App\Services\ValidacionService::class));

        $this->assertSame(0, $solicitud->fresh()->validaciones()->where('tipo', 'electoral')->count());
    }
}
