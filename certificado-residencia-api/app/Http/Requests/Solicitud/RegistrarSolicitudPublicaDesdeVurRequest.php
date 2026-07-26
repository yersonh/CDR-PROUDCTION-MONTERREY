<?php

namespace App\Http\Requests\Solicitud;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de una Solicitud Carta de Residencia que VUR radicó directamente
 * (correo o ventanilla presencial, sin pasar por el formulario público de
 * CDR). A diferencia de StoreSolicitudPublicaRequest, el operador de VUR no
 * captura tipo_certificado (es un concepto propio del formulario web) — por
 * eso casi todo es nullable, igual que StoreRecibidoVurRequest (incoming)
 * trata estos mismos datos.
 *
 * barrio_vereda_sector y medio_acreditacion sí se capturan desde VUR (a
 * partir de la versión que agrega esos campos al radicado de Carta de
 * Residencia) porque RecibidoVurService::procesarAutomaticamente() los
 * exige para poder auto-formalizar este canal y enrutarlo al mismo flujo de
 * validación (IA electoral / Funcionario SISBEN / Presidente JAC) que si
 * hubiera venido del formulario público — y barrio_vereda_sector además lo
 * imprime literalmente el certificado final.
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
            'barrio_vereda_sector' => ['nullable', 'string', 'max:255'],
            'medio_acreditacion' => ['nullable', 'string', Rule::in(['electoral', 'sisben', 'jac'])],
            // El radicado ya asignado en VUR (ej. "2026-000050") — se guarda
            // de una vez para que el correo de confirmación de VUR tenga el
            // código de seguimiento antes de que termine de armarse el
            // recibido completo (con PDF) vía /recibidos-vur.
            'radicado_vur' => ['required', 'string', 'max:30'],
        ];
    }
}
