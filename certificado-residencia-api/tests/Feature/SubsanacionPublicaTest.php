<?php

namespace Tests\Feature;

use App\DTOs\CreateSolicitudData;
use App\Enums\MedioAcreditacion;
use App\Enums\TipoCertificado;
use App\Models\Solicitud;
use App\Models\User;
use App\Notifications\SubsanacionRecibidaNotification;
use App\Services\SolicitudService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubsanacionPublicaTest extends TestCase
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

    private function radicar(array $overrides = []): Solicitud
    {
        $data = new CreateSolicitudData(
            nombreCompleto: $overrides['nombre_completo'] ?? 'Ciudadano de Prueba',
            tipoDocumento: 'CC',
            numeroIdentificacion: $overrides['numero_identificacion'] ?? '999',
            direccion: 'x',
            correo: 'ciudadano@example.com',
            celular: '3001112233',
            barrioVeredaSector: 'y',
            motivo: null,
            tipoCertificado: TipoCertificado::General,
            medioAcreditacion: MedioAcreditacion::from($overrides['medio_acreditacion'] ?? 'sisben'),
            soporte: $overrides['soporte'] ?? UploadedFile::fake()->create('s.pdf', 10, 'application/pdf'),
            createdBy: $this->usuarioCon('secretaria')->id,
        );

        return app(SolicitudService::class)->radicar($data);
    }

    private function ponerEnPendienteSoporte(Solicitud $solicitud): void
    {
        $secretaria = $this->usuarioCon('secretaria');
        Sanctum::actingAs($secretaria);

        $this->postJson("/api/v1/solicitudes/{$solicitud->id}/prevalidacion", [
            'resultado' => 'subsanar',
            'observacion' => 'El documento no es legible.',
        ])->assertOk()->assertJsonPath('data.estado.value', 'pendiente_soporte');
    }

    public function test_rechaza_solicitud_sin_firma_valida(): void
    {
        $solicitud = $this->radicar();

        $this->postJson("/api/v1/public/subsanacion/{$solicitud->id}", [
            'soporte' => UploadedFile::fake()->create('c.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_ciudadano_sube_correccion_con_enlace_firmado_y_secretaria_es_notificada(): void
    {
        Notification::fake();

        $solicitud = $this->radicar();
        $this->ponerEnPendienteSoporte($solicitud);

        $url = URL::temporarySignedRoute('public.subsanacion.show', now()->addDays(30), ['solicitud' => $solicitud->id]);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->getJson("/api/v1/public/subsanacion/{$solicitud->id}?{$query}")
            ->assertOk()
            ->assertJsonPath('data.estado', 'pendiente_soporte')
            ->assertJsonPath('data.observacion', 'El documento no es legible.');

        $this->postJson("/api/v1/public/subsanacion/{$solicitud->id}?{$query}", [
            'soporte' => UploadedFile::fake()->create('corregido.pdf', 10, 'application/pdf'),
        ])->assertOk()->assertJsonPath('data.estado', 'en_validacion');

        Notification::assertSentTo(
            User::role('secretaria')->get(),
            SubsanacionRecibidaNotification::class,
        );
    }

    public function test_enlace_no_sirve_dos_veces_porque_el_estado_ya_cambio(): void
    {
        $solicitud = $this->radicar();
        $this->ponerEnPendienteSoporte($solicitud);

        $url = URL::temporarySignedRoute('public.subsanacion.show', now()->addDays(30), ['solicitud' => $solicitud->id]);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->postJson("/api/v1/public/subsanacion/{$solicitud->id}?{$query}", [
            'soporte' => UploadedFile::fake()->create('corregido.pdf', 10, 'application/pdf'),
        ])->assertOk();

        // El mismo enlace, reutilizado: la firma sigue siendo válida, pero el
        // estado ya no es "pendiente_soporte" — el guard de negocio, no la
        // firma, es lo que impide reenviar dos veces.
        $this->postJson("/api/v1/public/subsanacion/{$solicitud->id}?{$query}", [
            'soporte' => UploadedFile::fake()->create('otra-vez.pdf', 10, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('estado');
    }
}
