<?php

namespace Tests\Feature;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\SolicitudService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlujoTramiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class]);
        Storage::fake('local');
    }

    private function usuarioCon(string $rol): User
    {
        $user = User::factory()->create(['password' => Hash::make('password'), 'activo' => true]);
        $user->assignRole($rol);

        return $user;
    }

    /**
     * Ya no hay endpoint de radicación manual (toda solicitud entra vía el
     * formulario público + VUR, o vía RecibidoVurService::procesarAutomaticamente)
     * — para los tests que solo necesitan una Solicitud existente como fixture,
     * se crea directamente por el servicio, igual que hace la auto-creación.
     */
    private function radicar(array $overrides = []): Solicitud
    {
        $data = new CreateSolicitudData(
            nombreCompleto: $overrides['nombre_completo'] ?? 'Test',
            tipoDocumento: 'CC',
            numeroIdentificacion: $overrides['numero_identificacion'] ?? '777',
            direccion: 'x',
            correo: 't@t.com',
            celular: '3001112233',
            barrioVeredaSector: 'y',
            motivo: null,
            tipoCertificado: TipoCertificado::General,
            medioAcreditacion: MedioAcreditacion::from($overrides['medio_acreditacion'] ?? 'electoral'),
            justificacionEspecial: $overrides['justificacion_especial'] ?? null,
            soporte: $overrides['soporte'] ?? null,
            createdBy: $this->usuarioCon('secretaria')->id,
        );

        return app(SolicitudService::class)->radicar($data);
    }

    public function test_login_devuelve_token(): void
    {
        $user = $this->usuarioCon('secretaria');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'roles', 'permisos']]);
    }

    public function test_login_falla_con_credenciales_invalidas(): void
    {
        $user = $this->usuarioCon('secretaria');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'malo'])
            ->assertStatus(422);
    }

    public function test_flujo_completo_hasta_certificado(): void
    {
        // 1. Solicitud radicada (vía el servicio, como la crearía el auto-enrutamiento)
        $id = $this->radicar([
            'nombre_completo' => 'Maria Lopez', 'numero_identificacion' => '555',
            'medio_acreditacion' => 'electoral',
            'soporte' => UploadedFile::fake()->create('e.pdf', 30, 'application/pdf'),
        ])->id;

        // 2. Secretaría valida y prevalida
        $secretaria = $this->usuarioCon('secretaria');
        Sanctum::actingAs($secretaria);
        $this->postJson("/api/v1/solicitudes/{$id}/validaciones", ['tipo' => 'electoral', 'resultado' => 'cumple'])
            ->assertCreated();
        $this->postJson("/api/v1/solicitudes/{$id}/prevalidacion", ['resultado' => 'cumple'])
            ->assertOk()->assertJsonPath('data.estado.value', 'preaprobada');

        // 3. Alcalde firma
        Sanctum::actingAs($this->usuarioCon('alcalde'));
        $this->postJson('/api/v1/certificados/firmar', ['solicitud_ids' => [$id]])
            ->assertOk()->assertJsonPath('firmadas.0', fn ($c) => str_starts_with($c, 'CR-'));

        // 4. Certificado emitido y estado certificada
        $detalle = $this->getJson("/api/v1/solicitudes/{$id}")->json('data');
        $this->assertSame('certificada', $detalle['estado']['value']);
        $this->assertNotNull($detalle['certificado']['consecutivo']);

        // 5. Consulta pública sin autenticación
        $codigo = $detalle['certificado']['codigo_verificacion'];
        $this->postJson('/api/v1/auth/logout'); // por si acaso
        $this->getJson("/api/v1/public/verificar/{$codigo}")
            ->assertOk()
            ->assertJsonPath('valido', true)
            ->assertJsonPath('vigente', true);
    }

    public function test_funcionario_sisben_no_puede_firmar(): void
    {
        Sanctum::actingAs($this->usuarioCon('funcionario_sisben'));
        $this->postJson('/api/v1/certificados/firmar', ['solicitud_ids' => [1]])
            ->assertForbidden();
    }

    public function test_funcionario_sisben_no_puede_prevalidar(): void
    {
        $id = $this->radicar([
            'medio_acreditacion' => 'especial', 'justificacion_especial' => 'motivo',
        ])->id;

        Sanctum::actingAs($this->usuarioCon('funcionario_sisben'));
        $this->postJson("/api/v1/solicitudes/{$id}/prevalidacion", ['resultado' => 'cumple'])
            ->assertForbidden();
    }
}
