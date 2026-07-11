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
      <Field label="Barrio, vereda o sector" htmlFor="barrio_vereda_sector" required error={errors.barrio_vereda_sector?.message}>
        <Input id="barrio_vereda_sector" aria-invalid={!!errors.barrio_vereda_sector} {...register('barrio_vereda_sector')} />
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
}

/** Paso 2 del wizard: tipo de certificado, medio de acreditación y soporte. */
export function CertificadoSoporteStep({ register, errors, catalogos, medio, soporte, soporteError, onSoporteChange }: CertificadoStepProps) {
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

      {medio === 'electoral' && (
        <Field label="Certificado electoral" htmlFor="soporte" required>
          <FileUpload file={soporte} onChange={onSoporteChange} error={soporteError} />
        </Field>
      )}

      {medio === 'especial' && (
        <Field label="Justificación administrativa" htmlFor="justificacion_especial" required
          hint="Explique por qué requiere un estudio especial" error={errors.justificacion_especial?.message}>
          <Textarea id="justificacion_especial" rows={4} aria-invalid={!!errors.justificacion_especial} {...register('justificacion_especial')} />
        </Field>
      )}

      {(medio === 'sisben' || medio === 'jac') && (
        <div className="rounded-lg border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
          El soporte de <strong>{medio === 'sisben' ? 'SISBEN' : 'la JAC'}</strong> será cargado por el
          funcionario autorizado una vez radicada la solicitud.
        </div>
      )}
    </div>
  )
}

/** Paso 3 del wizard: revisión antes de enviar. */
export function ReviewStep({
  values,
  soporte,
  catalogos,
}: {
  values: SolicitudFormValues
  soporte: File | null
  catalogos?: Catalogos
}) {
  const tipo = catalogos?.tipos_certificado.find((t) => t.value === values.tipo_certificado)?.label
  const medio = catalogos?.medios_acreditacion.find((m) => m.value === values.medio_acreditacion)?.label

  const rows: [string, string | undefined | null][] = [
    ['Nombre', values.nombre_completo],
    ['Documento', `${values.tipo_documento} ${values.numero_identificacion}`],
    ['Correo', values.correo],
    ['Celular', values.celular],
    ['Dirección', values.direccion],
    ['Barrio / vereda', values.barrio_vereda_sector],
    ['Motivo', values.motivo || '—'],
    ['Tipo de certificado', tipo],
    ['Medio de acreditación', medio],
    ['Justificación', values.justificacion_especial || undefined],
    ['Soporte adjunto', soporte?.name],
  ]

  return (
    <div>
      <p className="mb-3 text-sm text-institutional-muted">Revise la información antes de continuar:</p>
      <dl className="divide-y divide-institutional-border rounded-lg border border-institutional-border">
        {rows.filter(([, v]) => v).map(([k, v]) => (
          <div key={k} className="flex justify-between gap-4 px-4 py-2.5 text-sm">
            <dt className="text-institutional-muted">{k}</dt>
            <dd className="text-right font-medium text-institutional-text">{v}</dd>
          </div>
        ))}
      </dl>
    </div>
  )
}
