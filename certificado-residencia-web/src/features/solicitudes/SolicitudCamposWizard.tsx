import type { ChangeEvent } from 'react'
import type { UseFormRegister, FieldErrors } from 'react-hook-form'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Field } from '@/components/ui/field'
import { FileUpload } from '@/components/ui/file-upload'
import type { SolicitudFormValues } from './solicitud-schema'
import type { Catalogos } from './types'

interface StepProps {
  register: UseFormRegister<SolicitudFormValues>
  errors: FieldErrors<SolicitudFormValues>
  catalogos?: Catalogos
}

/** Bloquea en el propio input cualquier carácter que no sea dígito (evita depender solo del mensaje de error). */
function onlyDigits(e: ChangeEvent<HTMLInputElement>) {
  e.target.value = e.target.value.replace(/\D/g, '')
}

/** Paso 1 del wizard: datos del ciudadano. Compartido entre el flujo interno y el formulario público. */
export function DatosCiudadanoStep({ register, errors, catalogos }: StepProps) {
  const numeroIdentificacion = register('numero_identificacion')
  const celular = register('celular')

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <Field label="Nombre completo" htmlFor="nombre_completo" required error={errors.nombre_completo?.message} className="sm:col-span-2">
        <Input id="nombre_completo" aria-invalid={!!errors.nombre_completo} {...register('nombre_completo')} />
      </Field>
      <Field label="Tipo de documento" htmlFor="tipo_documento" required error={errors.tipo_documento?.message}>
        <Select id="tipo_documento" aria-invalid={!!errors.tipo_documento} {...register('tipo_documento')}>
          <option value="">Seleccione…</option>
          {catalogos?.tipos_documento.map((t) => <option key={t} value={t}>{t}</option>)}
        </Select>
      </Field>
      <Field label="Número de identificación" htmlFor="numero_identificacion" required error={errors.numero_identificacion?.message}>
        <Input
          id="numero_identificacion"
          inputMode="numeric"
          maxLength={15}
          aria-invalid={!!errors.numero_identificacion}
          {...numeroIdentificacion}
          onChange={(e) => { onlyDigits(e); return numeroIdentificacion.onChange(e) }}
        />
      </Field>
      <Field label="Correo electrónico" htmlFor="correo" required error={errors.correo?.message}>
        <Input id="correo" type="email" aria-invalid={!!errors.correo} {...register('correo')} />
      </Field>
      <Field label="Celular" htmlFor="celular" required error={errors.celular?.message}>
        <Input
          id="celular"
          inputMode="tel"
          maxLength={10}
          aria-invalid={!!errors.celular}
          {...celular}
          onChange={(e) => { onlyDigits(e); return celular.onChange(e) }}
        />
      </Field>
      <Field label="Dirección de residencia" htmlFor="direccion" required error={errors.direccion?.message} className="sm:col-span-2">
        <Input id="direccion" aria-invalid={!!errors.direccion} {...register('direccion')} />
      </Field>
      <Field label="Barrio, vereda o sector" htmlFor="sector_id" required error={errors.sector_id?.message}>
        <Select id="sector_id" aria-invalid={!!errors.sector_id} {...register('sector_id')}>
          <option value="">Seleccione…</option>
          {catalogos?.sectores.map((s) => <option key={s.id} value={s.id}>{s.nombre}</option>)}
        </Select>
      </Field>
      <Field label="Motivo de la solicitud" htmlFor="motivo" error={errors.motivo?.message}>
        <Input id="motivo" placeholder="Opcional" {...register('motivo')} />
      </Field>
    </div>
  )
}

interface CertificadoStepProps extends StepProps {
  medio: string | undefined
  soporte: File | null
  soporteError?: string
  onSoporteChange: (file: File | null) => void
  /**
   * En el wizard interno (staff radicando), SISBEN/JAC se deja para que el
   * funcionario cargue el soporte después. En el formulario público no hay
   * funcionario detrás en ese momento — si el ciudadano no lo sube aquí, no
   * lo sube nadie. Poner en `true` exige el archivo también para esos dos
   * medios, no solo electoral.
   */
  soporteInmediato?: boolean
}

const SOPORTE_LABEL: Record<string, string> = {
  electoral: 'Certificado electoral',
  sisben: 'Soporte de antigüedad SISBEN',
  jac: 'Certificación de la Junta de Acción Comunal (JAC)',
}

/** Paso 2 del wizard: tipo de certificado, medio de acreditación y soporte. */
export function CertificadoSoporteStep({
  register, errors, catalogos, medio, soporte, soporteError, onSoporteChange, soporteInmediato = false,
}: CertificadoStepProps) {
  // JAC queda fuera de "inmediato" a propósito: el ciudadano normalmente no
  // tiene ese documento a la mano (lo expide el Presidente JAC), así que por
  // ahora el formulario público solo muestra un aviso, igual que SISBEN en
  // el flujo interno. Se completa más adelante con la captura de datos del
  // Presidente JAC.
  const requiereSoporteAhora = medio === 'electoral' || (soporteInmediato && medio === 'sisben')

  return (
    <div className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Tipo de certificado" htmlFor="tipo_certificado" required error={errors.tipo_certificado?.message}>
          <Select id="tipo_certificado" aria-invalid={!!errors.tipo_certificado} {...register('tipo_certificado')}>
            <option value="">Seleccione…</option>
            {catalogos?.tipos_certificado.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
          </Select>
        </Field>
        <Field label="Medio de acreditación" htmlFor="medio_acreditacion" required error={errors.medio_acreditacion?.message}>
          <Select id="medio_acreditacion" aria-invalid={!!errors.medio_acreditacion} {...register('medio_acreditacion')}>
            <option value="">Seleccione…</option>
            {catalogos?.medios_acreditacion.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
          </Select>
        </Field>
      </div>

      {requiereSoporteAhora && (
        <Field label={SOPORTE_LABEL[medio ?? ''] ?? 'Soporte de acreditación'} htmlFor="soporte" required>
          <FileUpload file={soporte} onChange={onSoporteChange} error={soporteError} />
        </Field>
      )}

      {medio === 'especial' && (
        <Field label="Justificación administrativa" htmlFor="justificacion_especial" required
          hint="Explique por qué requiere un estudio especial" error={errors.justificacion_especial?.message}>
          <Textarea id="justificacion_especial" rows={4} aria-invalid={!!errors.justificacion_especial} {...register('justificacion_especial')} />
        </Field>
      )}

      {medio === 'jac' && (
        <div className="rounded-lg border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
          La certificación de la <strong>Junta de Acción Comunal</strong> la expide el Presidente JAC de su
          sector — no necesita adjuntarla aquí. Se tramitará directamente con él una vez radicada la solicitud.
        </div>
      )}

      {!soporteInmediato && medio === 'sisben' && (
        <div className="rounded-lg border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
          El soporte de <strong>SISBEN</strong> será cargado por el funcionario autorizado una vez radicada la solicitud.
        </div>
      )}
    </div>
  )
}
