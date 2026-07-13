<?php

namespace Tests\Feature;

use App\Enums\EstadoSolicitud;
use App\Models\RecibidoVur;
use App\Models\Solicitud;
use App\Models\SolicitudPublica;
use App\Models\User;
use App\Services\SolicitudService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecibidoVurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class]);
        Storage::fake('local');
        // QUEUE_CONNECTION=sync en tests → radicar()/cambiarEstado() disparan
        // NotificarEstadoRecibidoAVur de inmediato, que llamaría a VUR de
        // verdad si no se fakea.
        Http::fake();

        $servicio = User::factory()->create(['password' => Hash::make('password'), 'activo' => true]);
        $servicio->assignRole('super_admin');
        Sanctum::actingAs($servicio);

        // RecibidoVurService::procesarAutomaticamente() usa esta cuenta como
        // actor de las Solicitud que crea automáticamente (ver UserSeeder).
        User::factory()->create([
            'email' => 'servicio-vur@sistema.local',
            'activo' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'radicado_vur' => '2026-000100',
            'nombre_completo' => 'Ciudadano De Prueba',
            'tipo_documento' => 'CC',
            'numero_identificacion' => '123456789',
            'pdf' => UploadedFile::fake()->create('recibido.pdf', 20, 'application/pdf'),
            ...$overrides,
        ];
    }

    public function test_crea_recibido_con_referencia_cdr(): void
    {
        $res = $this->postJson('/api/v1/recibidos-vur', $this->payload(['referencia_cdr' => 1]));

        $res->assertCreated();
        $this->assertDatabaseHas('recibidos_vur', ['radicado_vur' => '2026-000100', 'referencia_cdr' => 1]);
    }

    public function test_reenvio_con_mismo_referencia_cdr_es_409_aunque_cambie_el_radicado(): void
    {
        $this->postJson('/api/v1/recibidos-vur', $this->payload(['referencia_cdr' => 5, 'radicado_vur' => '2026-000100']))
            ->assertCreated();

        // Mismo envío real (referencia_cdr=5), pero VUR lo reintenta con un
        // radicado_vur distinto (p. ej. tras un timeout y reintento) — debe
        // seguir detectándose como duplicado.
        $this->postJson('/api/v1/recibidos-vur', $this->payload(['referencia_cdr' => 5, 'radicado_vur' => '2026-000101']))
            ->assertStatus(409);

        $this->assertSame(1, RecibidoVur::where('referencia_cdr', 5)->count());
    }

    public function test_radicado_vur_repetido_no_bloquea_si_referencia_cdr_es_distinta(): void
    {
        // Caso central: un radicado_vur viejo (dato de prueba, o reset de
        // numeración en VUR) no debe bloquear un envío real nuevo si su
        // referencia_cdr es distinta.
        $this->postJson('/api/v1/recibidos-vur', $this->payload(['referencia_cdr' => 1, 'radicado_vur' => '2026-000013']))
            ->assertCreated();

        $this->postJson('/api/v1/recibidos-vur', $this->payload(['referencia_cdr' => 2, 'radicado_vur' => '2026-000013']))
            ->assertCreated();

        $this->assertSame(2, RecibidoVur::where('radicado_vur', '2026-000013')->count());
    }

    public function test_sin_referencia_cdr_deduplica_por_radicado_vur_como_antes(): void
    {
        $this->postJson('/api/v1/recibidos-vur', $this->payload(['radicado_vur' => '2026-000200']))
            ->assertCreated();

        $this->postJson('/api/v1/recibidos-vur', $this->payload(['radicado_vur' => '2026-000200']))
            ->assertStatus(409);

        $this->assertSame(1, RecibidoVur::where('radicado_vur', '2026-000200')->count());
    }

    private function crearSolicitudPublica(string $medio): SolicitudPublica
    {
        $origen = SolicitudPublica::create([
            'nombre_completo' => 'Luisa Herrera',
            'tipo_documento' => 'CC',
            'numero_identificacion' => '1089765688',
            'direccion' => 'Calle 1',
            'correo' => 'luisa@example.com',
            'celular' => '3141413413',
            'barrio_vereda_sector' => 'Centro',
            'tipo_certificado' => 'general',
            'medio_acreditacion' => $medio,
            'ruta_pdf' => 'solicitudes-publicas/1/borrador.pdf',
            'estado' => 'enviado',
        ]);

        Storage::disk('local')->put($origen->ruta_pdf, 'borrador');
        $origen->update([
            'ruta_soporte' => "solicitudes-publicas/{$origen->id}/soporte.pdf",
            'ruta_documento_identidad' => "solicitudes-publicas/{$origen->id}/cedula.pdf",
            'ruta_pdf_firmado' => "solicitudes-publicas/{$origen->id}/firmado.pdf",
        ]);
        Storage::disk('local')->put($origen->ruta_soporte, 'soporte');
        Storage::disk('local')->put($origen->ruta_documento_identidad, 'cedula');
        Storage::disk('local')->put($origen->ruta_pdf_firmado, 'firmado');

        return $origen->refresh();
    }

    public function test_recibido_sisben_con_referencia_valida_autocrea_solicitud(): void
    {
        $origen = $this->crearSolicitudPublica('sisben');

        $res = $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000300',
        ]));

        $res->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();
        $this->assertSame('en_tramite', $recibido->estado);
        $this->assertNotNull($recibido->solicitud_id);

        $solicitud = Solicitud::findOrFail($recibido->solicitud_id);
        $this->assertSame('sisben', $solicitud->medio_acreditacion->value);
        $this->assertSame('radicada', $solicitud->estado->value);
        $this->assertCount(3, $solicitud->expediente->documentos);
    }

    public function test_recibido_jac_con_referencia_valida_autocrea_solicitud(): void
    {
        $origen = $this->crearSolicitudPublica('jac');

        $res = $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000301',
        ]));

        $res->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();
        $this->assertSame('en_tramite', $recibido->estado);
        $this->assertNotNull($recibido->solicitud_id);
    }

    public function test_recibido_electoral_con_referencia_valida_autocrea_solicitud_y_despacha_validacion_ia(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $origen = $this->crearSolicitudPublica('electoral');

        $res = $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000302',
        ]));

        $res->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();
        $this->assertSame('en_tramite', $recibido->estado);
        $this->assertNotNull($recibido->solicitud_id);

        $solicitud = Solicitud::findOrFail($recibido->solicitud_id);
        $this->assertSame('electoral', $solicitud->medio_acreditacion->value);

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Jobs\ValidarCertificadoElectoralConIA::class,
            fn ($job) => (fn () => $this->solicitudId)->call($job) === $solicitud->id,
        );
    }

    public function test_recibido_pasa_a_procesado_cuando_la_solicitud_llega_a_terminal(): void
    {
        $origen = $this->crearSolicitudPublica('sisben');

        $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000303',
        ]))->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();
        $solicitud = Solicitud::findOrFail($recibido->solicitud_id);

        app(SolicitudService::class)->cambiarEstado($solicitud, EstadoSolicitud::Rechazada, 'No cumple (prueba)');

        $this->assertSame('procesado', $recibido->fresh()->estado);
    }

    public function test_no_notifica_en_tramite_al_autocrear_la_solicitud(): void
    {
        // Avisar "EN_TRAMITE" en el momento en que CDR crea la solicitud
        // automáticamente sería falso — nadie ha hecho nada todavía. Ese
        // aviso sale hasta que alguien registre el primer soporte.
        $origen = $this->crearSolicitudPublica('sisben');

        $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000305',
        ]))->assertCreated();

        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/estado'));
    }

    public function test_notifica_en_tramite_cuando_se_registra_el_primer_soporte(): void
    {
        $origen = $this->crearSolicitudPublica('sisben');

        $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000306',
        ]))->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();

        $this->postJson("/api/v1/solicitudes/{$recibido->solicitud_id}/validaciones", [
            'tipo' => 'sisben',
            'resultado' => 'cumple',
            'soporte' => UploadedFile::fake()->create('certificacion.pdf', 20, 'application/pdf'),
        ])->assertCreated();

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), "/2026-000306/estado")
            && $request['estado'] === 'EN_TRAMITE');
    }

    public function test_prevalidacion_no_permite_subsanar_para_sisben_o_jac(): void
    {
        $origen = $this->crearSolicitudPublica('sisben');

        $this->postJson('/api/v1/recibidos-vur', $this->payload([
            'referencia_cdr' => $origen->id,
            'radicado_vur' => '2026-000304',
        ]))->assertCreated();

        $recibido = RecibidoVur::where('referencia_cdr', $origen->id)->firstOrFail();

        $this->postJson("/api/v1/solicitudes/{$recibido->solicitud_id}/prevalidacion", [
            'resultado' => 'subsanar',
            'observacion' => 'Documento borroso',
        ])->assertStatus(422)->assertJsonValidationErrors('resultado');
    }
}
