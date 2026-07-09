<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_login_devuelve_token(): void
    {
        $user = $this->usuarioCon('ciudadano');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'roles', 'permisos']]);
    }

    public function test_login_falla_con_credenciales_invalidas(): void
    {
        $user = $this->usuarioCon('ciudadano');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'malo'])
            ->assertStatus(422);
    }

    public function test_ciudadano_puede_radicar_con_soporte(): void
    {
        Sanctum::actingAs($this->usuarioCon('ciudadano'));

        $res = $this->postJson('/api/v1/solicitudes', [
            'nombre_completo' => 'Juan Perez',
            'tipo_documento' => 'CC',
            'numero_identificacion' => '123456',
            'direccion' => 'Calle 1',
            'correo' => 'juan@example.com',
            'celular' => '3001112233',
            'barrio_vereda_sector' => 'Centro',
            'tipo_certificado' => 'general',
            'medio_acreditacion' => 'electoral',
            'soporte' => UploadedFile::fake()->create('soporte.pdf', 50, 'application/pdf'),
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.estado.value', 'radicada');

        $this->assertMatchesRegularExpression('/^R-\d{4}-\d{6}$/', $res->json('data.radicado'));
        $this->assertNotNull($res->json('data.expediente.codigo'));
        $this->assertNotNull($res->json('data.sla.fecha_limite'));
        $this->assertCount(1, $res->json('data.expediente.documentos'));
    }

    public function test_radicacion_electoral_exige_soporte(): void
    {
        Sanctum::actingAs($this->usuarioCon('ciudadano'));

        $this->postJson('/api/v1/solicitudes', [
            'nombre_completo' => 'Ana', 'numero_identificacion' => '9', 'direccion' => 'x',
            'correo' => 'a@a.com', 'celular' => '3', 'barrio_vereda_sector' => 'y',
            'tipo_certificado' => 'general', 'medio_acreditacion' => 'electoral',
        ])->assertStatus(422)->assertJsonValidationErrors('soporte');
    }

    public function test_flujo_completo_hasta_certificado(): void
    {
        // 1. Ciudadano radica
        $ciudadano = $this->usuarioCon('ciudadano');
        Sanctum::actingAs($ciudadano);
        $id = $this->postJson('/api/v1/solicitudes', [
            'nombre_completo' => 'Maria Lopez', 'tipo_documento' => 'CC', 'numero_identificacion' => '555',
            'direccion' => 'Cra 2', 'correo' => 'maria@example.com', 'celular' => '3009998877',
            'barrio_vereda_sector' => 'Norte', 'tipo_certificado' => 'general', 'medio_acreditacion' => 'electoral',
            'soporte' => UploadedFile::fake()->create('e.pdf', 30, 'application/pdf'),
        ])->json('data.id');

        // 2. Operador valida y prevalida
        Sanctum::actingAs($this->usuarioCon('operador'));
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

    public function test_operador_no_puede_firmar(): void
    {
        Sanctum::actingAs($this->usuarioCon('operador'));
        $this->postJson('/api/v1/certificados/firmar', ['solicitud_ids' => [1]])
            ->assertForbidden();
    }

    public function test_ciudadano_no_puede_prevalidar(): void
    {
        Sanctum::actingAs($this->usuarioCon('ciudadano'));

        $id = $this->postJson('/api/v1/solicitudes', [
            'nombre_completo' => 'Test', 'tipo_documento' => 'CC', 'numero_identificacion' => '777',
            'direccion' => 'x', 'correo' => 't@t.com', 'celular' => '3', 'barrio_vereda_sector' => 'y',
            'tipo_certificado' => 'general', 'medio_acreditacion' => 'especial', 'justificacion_especial' => 'motivo',
        ])->json('data.id');

        $this->postJson("/api/v1/solicitudes/{$id}/prevalidacion", ['resultado' => 'cumple'])
            ->assertForbidden();
    }
}
