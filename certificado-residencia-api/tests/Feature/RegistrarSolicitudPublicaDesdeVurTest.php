<?php

namespace Tests\Feature;

use App\Models\SolicitudPublica;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrarSolicitudPublicaDesdeVurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'nombre_completo' => 'Ciudadano Radicado Presencial',
            'tipo_documento' => 'CC',
            'numero_identificacion' => '1122334455',
            'direccion' => 'Calle 1 # 2-3',
            'correo' => 'presencial@example.com',
            'celular' => '3001112233',
            'motivo' => 'Radicado en ventanilla presencial',
            'radicado_vur' => '2026-000099',
            ...$overrides,
        ];
    }

    public function test_servicio_vur_puede_registrar_y_recibe_codigo(): void
    {
        $servicioVur = User::where('email', 'servicio-vur@sistema.local')->firstOrFail();
        Sanctum::actingAs($servicioVur);

        $res = $this->postJson('/api/v1/solicitudes-publicas/registrar-desde-vur', $this->payload());

        $res->assertCreated();
        $solicitud = SolicitudPublica::firstOrFail();

        $res->assertJson([
            'data' => [
                'referencia_cdr' => $solicitud->id,
                'codigo_seguimiento_cdr' => 'SP-'.str_pad((string) $solicitud->id, 8, '0', STR_PAD_LEFT),
            ],
        ]);

        $this->assertSame('enviado', $solicitud->estado);
        $this->assertSame('2026-000099', $solicitud->radicado_vur);
        $this->assertNull($solicitud->tipo_certificado);
        $this->assertNull($solicitud->medio_acreditacion);
        $this->assertNull($solicitud->ruta_pdf);
    }

    public function test_usuario_sin_permiso_no_puede_registrar(): void
    {
        $sinPermiso = User::factory()->create(['activo' => true]);
        Sanctum::actingAs($sinPermiso);

        $this->postJson('/api/v1/solicitudes-publicas/registrar-desde-vur', $this->payload())
            ->assertForbidden();
    }

    public function test_consultar_solicitud_registrada_desde_vur_no_revienta_sin_tipo_certificado(): void
    {
        $servicioVur = User::where('email', 'servicio-vur@sistema.local')->firstOrFail();
        Sanctum::actingAs($servicioVur);

        $this->postJson('/api/v1/solicitudes-publicas/registrar-desde-vur', $this->payload())
            ->assertCreated();

        $solicitud = SolicitudPublica::firstOrFail();
        $referencia = 'SP-'.str_pad((string) $solicitud->id, 8, '0', STR_PAD_LEFT);

        $res = $this->getJson("/api/v1/public/solicitudes/{$referencia}");

        $res->assertOk();
        $res->assertJsonPath('data.tipo_certificado', 'Carta de residencia');
        $res->assertJsonPath('data.radicado_vur', '2026-000099');
    }
}
