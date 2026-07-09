import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link } from 'react-router-dom'
import { ArrowLeft, ArrowRight, CheckCircle2, Send } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Field } from '@/components/ui/field'
import { FileUpload } from '@/components/ui/file-upload'
import { getApiErrorMessage } from '@/lib/api'
import { useCatalogos } from '@/features/catalogos/useCatalogos'
import { useCreateSolicitud, type CreateSolicitudResult } from './api'
import { Stepper } from './Stepper'
import { solicitudSchema, STEP_FIELDS, type SolicitudFormValues } from './solicitud-schema'

const STEPS = ['Datos del ciudadano', 'Certificado y soporte', 'Confirmación']

export function NuevaSolicitudPage() {
  const { data: catalogos } = useCatalogos()
  const crear = useCreateSolicitud()
  const [step, setStep] = useState(0)
  const [soporte, setSoporte] = useState<File | null>(null)
  const [soporteError, setSoporteError] = useState<string>()
  const [result, setResult] = useState<CreateSolicitudResult | null>(null)

  const form = useForm<SolicitudFormValues>({
    resolver: zodResolver(solicitudSchema),
    mode: 'onTouched',
    defaultValues: {
      nombre_completo: '', tipo_documento: '', numero_identificacion: '', direccion: '',
      correo: '', celular: '', barrio_vereda_sector: '', motivo: '',
      tipo_certificado: '', medio_acreditacion: '', justificacion_especial: '',
    },
  })
  const { register, formState: { errors }, trigger, watch, getValues } = form
  const medio = watch('medio_acreditacion')

  const next = async () => {
    const ok = await trigger(STEP_FIELDS[step])
    if (step === 1 && medio === 'electoral' && !soporte) {
      setSoporteError('Debe adjuntar el certificado electoral')
      return
    }
    setSoporteError(undefined)
    if (ok) setStep((s) => Math.min(s + 1, STEPS.length - 1))
  }

  const submit = () => {
    const v = getValues()
    const fd = new FormData()
    Object.entries(v).forEach(([k, val]) => {
      if (val) fd.append(k, val as string)
    })
    if (soporte) fd.append('soporte', soporte)

    crear.mutate(fd, { onSuccess: (data) => setResult(data) })
  }

  // -------------------- Pantalla de éxito --------------------
  if (result) {
    const s = result.data
    return (
      <div className="mx-auto max-w-xl animate-fade-up">
        <Card>
          <CardContent className="flex flex-col items-center py-10 text-center">
            <CheckCircle2 className="h-16 w-16 text-success" />
            <h1 className="mt-4 text-2xl font-bold text-institutional-text">¡Solicitud radicada!</h1>
            <p className="mt-1 text-institutional-muted">{result.message}</p>

            <div className="mt-6 w-full rounded-xl border border-institutional-border bg-institutional-bg p-5">
              <p className="text-xs uppercase tracking-wide text-institutional-muted">Número de radicación</p>
              <p className="mt-1 text-3xl font-bold tracking-tight text-primary">{s.radicado}</p>
              <dl className="mt-4 grid grid-cols-2 gap-3 text-left text-sm">
                <div>
                  <dt className="text-institutional-muted">Expediente</dt>
                  <dd className="font-medium">{s.expediente?.codigo}</dd>
                </div>
                <div>
                  <dt className="text-institutional-muted">Estado</dt>
                  <dd className="font-medium">{s.estado.label}</dd>
                </div>
                <div>
                  <dt className="text-institutional-muted">Fecha límite</dt>
                  <dd className="font-medium">{s.sla.fecha_limite?.slice(0, 10)}</dd>
                </div>
                <div>
                  <dt className="text-institutional-muted">Tipo</dt>
                  <dd className="font-medium">{s.tipo_certificado.label}</dd>
                </div>
              </dl>
            </div>

            <div className="mt-6 flex gap-3">
              <Link to={`/solicitudes/${s.id}`}>
                <Button variant="primary">Ver seguimiento</Button>
              </Link>
              <Link to="/solicitudes">
                <Button variant="outline">Mis solicitudes</Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  // -------------------- Wizard --------------------
  return (
    <div className="mx-auto max-w-2xl animate-fade-up">
      <div className="mb-6">
        <Link to="/solicitudes" className="mb-3 inline-flex items-center gap-1 text-sm text-institutional-muted hover:text-primary">
          <ArrowLeft className="h-4 w-4" /> Volver
        </Link>
        <h1 className="text-2xl font-bold text-institutional-text">Nueva solicitud de residencia</h1>
        <p className="text-institutional-muted">Diligencie el formulario para radicar su trámite.</p>
      </div>

      <div className="mb-6">
        <Stepper steps={STEPS} current={step} />
      </div>

      <Card>
        <CardContent className="space-y-5">
          {crear.isError && (
            <div role="alert" className="rounded-lg border border-danger/40 bg-red-50 px-4 py-3 text-sm text-danger">
              {getApiErrorMessage(crear.error, 'No fue posible radicar la solicitud.')}
            </div>
          )}

          {/* Paso 1: Datos del ciudadano */}
          {step === 0 && (
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
                <Input id="numero_identificacion" inputMode="numeric" aria-invalid={!!errors.numero_identificacion} {...register('numero_identificacion')} />
              </Field>
              <Field label="Correo electrónico" htmlFor="correo" required error={errors.correo?.message}>
                <Input id="correo" type="email" aria-invalid={!!errors.correo} {...register('correo')} />
              </Field>
              <Field label="Celular" htmlFor="celular" required error={errors.celular?.message}>
                <Input id="celular" inputMode="tel" aria-invalid={!!errors.celular} {...register('celular')} />
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
          )}

          {/* Paso 2: Certificado y soporte */}
          {step === 1 && (
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
                  <FileUpload file={soporte} onChange={(f) => { setSoporte(f); setSoporteError(undefined) }} error={soporteError} />
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
          )}

          {/* Paso 3: Confirmación */}
          {step === 2 && (
            <ReviewStep values={getValues()} soporte={soporte} catalogos={catalogos} />
          )}

          {/* Navegación */}
          <div className="flex items-center justify-between border-t border-institutional-border pt-5">
            <Button variant="ghost" onClick={() => setStep((s) => Math.max(s - 1, 0))} disabled={step === 0}>
              <ArrowLeft className="h-4 w-4" /> Atrás
            </Button>
            {step < STEPS.length - 1 ? (
              <Button variant="primary" onClick={next}>
                Continuar <ArrowRight className="h-4 w-4" />
              </Button>
            ) : (
              <Button variant="success" onClick={submit} loading={crear.isPending}>
                <Send className="h-4 w-4" /> Radicar solicitud
              </Button>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function ReviewStep({
  values,
  soporte,
  catalogos,
}: {
  values: SolicitudFormValues
  soporte: File | null
  catalogos?: import('./types').Catalogos
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
      <p className="mb-3 text-sm text-institutional-muted">Revise la información antes de radicar:</p>
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
