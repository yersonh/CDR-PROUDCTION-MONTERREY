<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de una Solicitud Carta de Residencia que VUR radicó directamente
 * (correo o ventanilla presencial, sin pasar por el formulario público de
 * CDR). A diferencia de StoreSolicitudPublicaRequest, el operador de VUR no
 * captura tipo_certificado/medio_acreditacion/barrio_vereda_sector (son
 * conceptos propios del formulario web) — por eso casi todo es nullable,
 * igual que StoreRecibidoVurRequest (incoming) trata estos mismos datos.
 */
class RegistrarSolicitudPublicaDesdeVurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('solicitudes-publicas.crear-desde-vur') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['nullable', 'string', Rule::in(['CC', 'TI', 'CE', 'PA', 'PEP', 'NIT'])],
            'numero_identificacion' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'celular' => ['nullable', 'string', 'max:30'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            // El radicado ya asignado en VUR (ej. "2026-000050") — se guarda
            // de una vez para que el correo de confirmación de VUR tenga el
            // código de seguimiento antes de que termine de armarse el
            // recibido completo (con PDF) vía /recibidos-vur.
            'radicado_vur' => ['required', 'string', 'max:30'],
        ];
    }
}
