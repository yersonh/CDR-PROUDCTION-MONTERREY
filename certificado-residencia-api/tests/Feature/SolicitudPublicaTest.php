<?php

namespace Tests\Feature;

use App\Jobs\EnviarSolicitudPublicaAVur;
use App\Models\SolicitudPublica;
use App\Services\ClienteVur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SolicitudPublicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function payload(array $overrides = []): array
    {
        return [
            'nombre_completo' => 'Ana Torres',
            'tipo_documento' => 'CC',
            'numero_identificacion' => '111222333',
            'direccion' => 'Calle 5 # 6-7',
            'correo' => 'ana@example.com',
            'celular' => '3001112233',
            'barrio_vereda_sector' => 'Centro',
            'tipo_certificado' => 'general',
            'medio_acreditacion' => 'sisben',
            ...$overrides,
        ];
    }

    /** payload() + documento_identidad, para los tests que sí envían al endpoint de creación (la vista previa no lo pide). */
    private function payloadConDocumentoIdentidad(array $overrides = []): array
    {
        return [
            ...$this->payload($overrides),
            'documento_identidad' => UploadedFile::fake()->create('cedula.pdf', 10, 'application/pdf'),
        ];
    }

    /** payloadConDocumentoIdentidad() + documento_firmado, para los tests de creación exitosa completa. */
    private function payloadCompleto(array $overrides = []): array
    {
        return [
            ...$this->payloadConDocumentoIdentidad($overrides),
            'documento_firmado' => UploadedFile::fake()->create('solicitud_firmada.pdf', 15, 'application/pdf'),
        ];
    }

    public function test_vista_previa_devuelve_pdf_sin_persistir_nada(): void
    {
        $res = $this->postJson('/api/v1/public/solicitudes/preview', $this->payload());

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertDatabaseCount('solicitudes_publicas', 0);
    }

    public function test_vista_previa_valida_campos_requeridos(): void
    {
        $this->postJson('/api/v1/public/solicitudes/preview', $this->payload(['nombre_completo' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre_completo');
    }

    public function test_ciudadano_puede_enviar_solicitud_publica_sin_autenticacion(): void
    {
        Queue::fake();

        $res = $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto([
            'soporte' => UploadedFile::fake()->create('sisben.pdf', 20, 'application/pdf'),
        ]));

        $res->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonStructure(['data' => ['referencia', 'estado'], 'message']);

        $this->assertDatabaseCount('solicitudes_publicas', 1);
        $solicitud = SolicitudPublica::first();
        $this->assertTrue(Storage::disk('local')->exists($solicitud->ruta_pdf));
        $this->assertTrue(Storage::disk('local')->exists($solicitud->ruta_documento_identidad));
        $this->assertTrue(Storage::disk('local')->exists($solicitud->ruta_soporte));
        $this->assertTrue(Storage::disk('local')->exists($solicitud->ruta_pdf_firmado));

        Queue::assertPushed(EnviarSolicitudPublicaAVur::class);
    }

    public function test_documento_identidad_es_obligatorio(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('documento_identidad');
    }

    public function test_documento_firmado_es_obligatorio(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadConDocumentoIdentidad())
            ->assertStatus(422)
            ->assertJsonValidationErrors('documento_firmado');
    }

    public function test_documento_firmado_debe_ser_pdf(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadConDocumentoIdentidad([
            'documento_firmado' => UploadedFile::fake()->image('firmado.jpg'),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('documento_firmado');
    }

    public function test_electoral_exige_soporte(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto(['medio_acreditacion' => 'electoral']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('soporte');
    }

    public function test_sisben_exige_soporte(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto(['medio_acreditacion' => 'sisben']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('soporte');
    }

    public function test_jac_no_exige_soporte(): void
    {
        // JAC queda fuera a propósito: el ciudadano no tiene ese documento a
        // la mano (lo expide el Presidente JAC), así que el formulario
        // público no debe bloquear el envío por falta de "soporte" aquí.
        Queue::fake();

        $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto(['medio_acreditacion' => 'jac']))
            ->assertCreated();
    }

    public function test_especial_exige_justificacion(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto(['medio_acreditacion' => 'especial']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('justificacion_especial');
    }

    public function test_honeypot_rechaza_bots(): void
    {
        $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto(['sitio_web' => 'http://spam.example']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sitio_web');
    }

    public function test_acepta_soporte_electoral_adjunto(): void
    {
        Queue::fake();

        $res = $this->postJson('/api/v1/public/solicitudes', $this->payloadCompleto([
            'medio_acreditacion' => 'electoral',
            'soporte' => UploadedFile::fake()->create('certificado.pdf', 40, 'application/pdf'),
        ]));

        $res->assertCreated();
        $this->assertNotNull(SolicitudPublica::first()->ruta_soporte);
    }

    public function test_job_envia_a_vur_y_marca_enviado(): void
    {
        // El body del request multipart se lee sobre streams de archivo que
        // ClienteVur cierra apenas termina el POST — hay que capturar el
        // contenido dentro del fake (mientras el stream sigue vivo), no
        // después con assertSent().
        $bodyEnviado = null;
        Http::fake(function ($request) use (&$bodyEnviado) {
            $bodyEnviado = (string) $request->body();

            return Http::response(['radicado_vur' => 'VUR-2026-000123'], 200);
        });

        $solicitud = SolicitudPublica::create([
            ...$this->payload(),
            'ruta_pdf' => 'solicitudes-publicas/1/solicitud_1.pdf',
            'ruta_pdf_firmado' => 'solicitudes-publicas/1/solicitud_firmada_1.pdf',
            'ruta_documento_identidad' => 'solicitudes-publicas/1/cedula.pdf',
            'estado' => 'pendiente',
        ]);
        Storage::disk('local')->put($solicitud->ruta_pdf, 'contenido-pdf-borrador-de-prueba');
        Storage::disk('local')->put($solicitud->ruta_pdf_firmado, 'contenido-pdf-firmado-de-prueba');
        Storage::disk('local')->put($solicitud->ruta_documento_identidad, 'contenido-cedula-de-prueba');

        (new EnviarSolicitudPublicaAVur($solicitud->id))->handle(app(ClienteVur::class));

        $solicitud->refresh();
        $this->assertSame('enviado', $solicitud->estado);
        $this->assertSame('VUR-2026-000123', $solicitud->radicado_vur);
        $this->assertNotNull($solicitud->enviado_at);
        $this->assertStringContainsString('name="documento_identidad"', $bodyEnviado);
        // El pdf_solicitud enviado debe ser el FIRMADO, no el borrador.
        $this->assertStringContainsString('name="pdf_solicitud"; filename="solicitud_firmada_1.pdf"', $bodyEnviado);
        $this->assertStringContainsString('contenido-pdf-firmado-de-prueba', $bodyEnviado);
        $this->assertStringNotContainsString('contenido-pdf-borrador-de-prueba', $bodyEnviado);
    }

    public function test_job_marca_error_y_reintenta_si_vur_falla(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Internal error'], 500),
        ]);

        $solicitud = SolicitudPublica::create([
            ...$this->payload(),
            'ruta_pdf' => 'solicitudes-publicas/1/solicitud_1.pdf',
            'ruta_pdf_firmado' => 'solicitudes-publicas/1/solicitud_firmada_1.pdf',
            'estado' => 'pendiente',
        ]);
        Storage::disk('local')->put($solicitud->ruta_pdf, 'contenido-pdf-de-prueba');
        Storage::disk('local')->put($solicitud->ruta_pdf_firmado, 'contenido-pdf-firmado-de-prueba');

        $this->expectException(RuntimeException::class);

        try {
            (new EnviarSolicitudPublicaAVur($solicitud->id))->handle(app(ClienteVur::class));
        } finally {
            $solicitud->refresh();
            $this->assertSame('error', $solicitud->estado);
            $this->assertStringContainsString('HTTP 500', $solicitud->ultimo_error);
        }
    }
}
