<?php

namespace Tests\Feature;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\SolicitudService;
use App\Services\ValidacionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificacionesTest extends TestCase
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

    private function radicar(string $medio, string $createdByRol = 'secretaria'): Solicitud
    {
        $data = new CreateSolicitudData(
            nombreCompleto: 'Test',
            tipoDocumento: 'CC',
            numeroIdentificacion: '999',
            direccion: 'x',
            correo: 't@t.com',
            celular: '3001112233',
            barrioVeredaSector: 'y',
            motivo: null,
            tipoCertificado: TipoCertificado::General,
            medioAcreditacion: MedioAcreditacion::from($medio),
            justificacionEspecial: $medio === 'especial' ? 'motivo' : null,
            soporte: null,
            createdBy: $this->usuarioCon($createdByRol)->id,
        );

        return app(SolicitudService::class)->radicar($data);
    }

    public function test_solicitud_sisben_notifica_al_funcionario_sisben(): void
    {
        $sisben = $this->usuarioCon('funcionario_sisben');

        $this->radicar('sisben');

        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $sisben->id,
            'tipo' => 'solicitud.nueva',
        ]);
    }

    public function test_solicitud_jac_notifica_al_presidente_jac_no_al_sisben(): void
    {
        $jac = $this->usuarioCon('presidente_jac');
        $sisben = $this->usuarioCon('funcionario_sisben');

        $this->radicar('jac');

        $this->assertDatabaseHas('notificaciones', ['user_id' => $jac->id]);
        $this->assertDatabaseMissing('notificaciones', ['user_id' => $sisben->id]);
    }

    public function test_solicitud_electoral_notifica_a_secretaria(): void
    {
        $secretaria = $this->usuarioCon('secretaria');

        $this->radicar('electoral', 'secretaria');

        // Puede haber más de una fila de secretaría (el usuario que crea +
        // el destinatario son el mismo aquí), lo importante es que existe.
        $this->assertDatabaseHas('notificaciones', ['user_id' => $secretaria->id]);
    }

    public function test_endpoint_no_leidas_cuenta_solo_las_del_usuario_autenticado(): void
    {
        $sisben = $this->usuarioCon('funcionario_sisben');
        $this->usuarioCon('presidente_jac'); // otro usuario, no debe contar

        $this->radicar('sisben');
        $this->radicar('jac');

        Sanctum::actingAs($sisben);
        $this->getJson('/api/v1/notificaciones/no-leidas')
            ->assertOk()
            ->assertJsonPath('no_leidas', 1);
    }

    public function test_marcar_leida_baja_el_contador_y_no_permite_marcar_ajenas(): void
    {
        $sisben = $this->usuarioCon('funcionario_sisben');
        $otro = $this->usuarioCon('presidente_jac');

        $this->radicar('sisben');
        $this->radicar('jac');

        Sanctum::actingAs($sisben);
        $notificacionPropia = $sisben->notificaciones()->first();
        $notificacionAjena = $otro->notificaciones()->first();

        $this->patchJson("/api/v1/notificaciones/{$notificacionPropia->id}/leer")->assertOk();
        $this->getJson('/api/v1/notificaciones/no-leidas')->assertJsonPath('no_leidas', 0);

        $this->patchJson("/api/v1/notificaciones/{$notificacionAjena->id}/leer")->assertForbidden();
    }

    public function test_respuesta_sisben_del_especialista_notifica_a_secretaria(): void
    {
        $secretaria = $this->usuarioCon('secretaria');
        $sisben = $this->usuarioCon('funcionario_sisben');
        $solicitud = $this->radicar('sisben');

        Sanctum::actingAs($sisben);
        $this->postJson("/api/v1/solicitudes/{$solicitud->id}/validaciones", [
            'tipo' => 'sisben',
            'resultado' => 'cumple',
            'soporte' => \Illuminate\Http\UploadedFile::fake()->create('respuesta.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $this->assertDatabaseHas('notificaciones', [
            'user_id' => $secretaria->id,
            'solicitud_id' => $solicitud->id,
        ]);
    }

    public function test_respuesta_electoral_no_notifica_de_nuevo_a_secretaria(): void
    {
        $secretaria = $this->usuarioCon('secretaria');
        $solicitud = $this->radicar('electoral', 'secretaria');
        // radicar() ya generó notificaciones (para $secretaria y para el
        // usuario "secretaria" auxiliar creado como createdBy); las
        // limpiamos todas para aislar el efecto de registrarSoporte.
        \App\Models\Notificacion::query()->delete();

        Sanctum::actingAs($secretaria);
        app(ValidacionService::class)->registrarSoporte(
            $solicitud->fresh(),
            'electoral',
            null,
            null,
            \App\Enums\ResultadoValidacion::Cumple,
            null,
            $secretaria,
        );

        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_marcar_todas_leidas(): void
    {
        $sisben = $this->usuarioCon('funcionario_sisben');
        $this->radicar('sisben');
        $this->radicar('sisben');

        Sanctum::actingAs($sisben);
        $this->getJson('/api/v1/notificaciones/no-leidas')->assertJsonPath('no_leidas', 2);

        $this->patchJson('/api/v1/notificaciones/leer-todas')->assertOk();
        $this->getJson('/api/v1/notificaciones/no-leidas')->assertJsonPath('no_leidas', 0);
    }
}
