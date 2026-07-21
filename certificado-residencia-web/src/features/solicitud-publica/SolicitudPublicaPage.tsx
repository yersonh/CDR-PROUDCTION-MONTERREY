import { useCallback, useEffect, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link } from 'react-router-dom'
import { ArrowLeft, ArrowRight, CheckCircle2, Download, FileWarning, Search, Send, ShieldCheck } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Field } from '@/components/ui/field'
import { FileUpload } from '@/components/ui/file-upload'
import { getApiErrorMessage } from '@/lib/api'
import { loadDraft, saveDraft, clearDraft, type SolicitudPublicaDraft } from '@/lib/draftStorage'
import { usePublicCatalogos } from '@/features/catalogos/useCatalogos'
import { Stepper } from '@/features/solicitudes/Stepper'
import { DatosCiudadanoStep, CertificadoSoporteStep } from '@/features/solicitudes/SolicitudCamposWizard'
import { solicitudSchema, STEP_FIELDS, type SolicitudFormValues } from '@/features/solicitudes/solicitud-schema'
import { useCreateSolicitudPublica, usePreviewSolicitudPublica, type CreateSolicitudPublicaResult } from './api'
import { NexGovIAInfoModal } from '@/components/nexgovia-info-modal'
import escudo from '@/assets/logo-alcaldia.png'
import fondoLogin from '@/assets/fondo-formulario-publico.png'

const STEPS = ['Datos del ciudadano', 'Certificado y soporte', 'Confirmación']

const DEFAULT_VALUES: SolicitudFormValues = {
  nombre_completo: '', tipo_documento: '', numero_identificacion: '', direccion: '',
  correo: '', celular: '', barrio_vereda_sector: '', sector_id: '', motivo: '',
  tipo_certificado: '', medio_acreditacion: '',
}

export function SolicitudPublicaPage() {
  const { data: catalogos } = usePublicCatalogos()
  const crear = useCreateSolicitudPublica()
  const preview = usePreviewSolicitudPublica()
  const [step, setStep] = useState(0)
  const [soporte, setSoporte] = useState<File | null>(null)
  const [soporteError, setSoporteError] = useState<string>()
  const [documentoIdentidad, setDocumentoIdentidad] = useState<File | null>(null)
  const [documentoIdentidadError, setDocumentoIdentidadError] = useState<string>()
  const [documentoFirmado, setDocumentoFirmado] = useState<File | null>(null)
  const [documentoFirmadoError, setDocumentoFirmadoError] = useState<string>()
  const [result, setResult] = useState<CreateSolicitudPublicaResult | null>(null)
  const [showNexGovIA, setShowNexGovIA] = useState(false)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const previewUrlRef = useRef<string | null>(null)

  // Borrador recuperado de IndexedDB al cargar la página, pendiente de que el
  // ciudadano decida si lo retoma o empieza de nuevo.
  const [pendingDraft, setPendingDraft] = useState<SolicitudPublicaDraft | null>(null)
  const [draftChecked, setDraftChecked] = useState(false)

  const form = useForm<SolicitudFormValues>({
    resolver: zodResolver(solicitudSchema),
    mode: 'onTouched',
    defaultValues: DEFAULT_VALUES,
  })
  const { register, formState: { errors }, trigger, watch, getValues, reset } = form
  const medio = watch('medio_acreditacion')

  // Libera el objeto Blob anterior cada vez que se genera uno nuevo o al desmontar.
  useEffect(() => () => { if (previewUrlRef.current) URL.revokeObjectURL(previewUrlRef.current) }, [])

  // Al montar, revisa si hay un borrador guardado en este navegador.
  useEffect(() => {
    loadDraft().then((draft) => {
      setPendingDraft(draft)
      setDraftChecked(true)
    })
  }, [])

  // Autoguardado: cada cambio relevante del formulario/archivos/paso se
  // persiste (con un pequeño debounce) mientras no haya un borrador
  // pendiente de decisión — el ciudadano puede tardar en imprimir, firmar,
  // escanear y volver a subir el documento, y no debe perder lo diligenciado.
  const saveTimeout = useRef<ReturnType<typeof setTimeout> | null>(null)
  const queueSave = useCallback(() => {
    if (pendingDraft || result) return
    if (saveTimeout.current) clearTimeout(saveTimeout.current)
    saveTimeout.current = setTimeout(() => {
      saveDraft({ formValues: getValues(), documentoIdentidad, soporte, documentoFirmado, step })
    }, 500)
  }, [pendingDraft, result, getValues, documentoIdentidad, soporte, documentoFirmado, step])

  useEffect(() => {
    const sub = watch(() => queueSave())
    return () => sub.unsubscribe()
  }, [watch, queueSave])

  useEffect(() => { queueSave() }, [step, soporte, documentoIdentidad, documentoFirmado, queueSave])

  const continuarBorrador = () => {
    if (!pendingDraft) return
    reset(pendingDraft.formValues)
    setSoporte(pendingDraft.soporte)
    setDocumentoIdentidad(pendingDraft.documentoIdentidad)
    setDocumentoFirmado(pendingDraft.documentoFirmado)
    setStep(pendingDraft.step)
    setPendingDraft(null)
    // La vista previa (previewUrl) es un blob en memoria, no algo que se
    // guarde en el borrador — si el borrador retomado ya estaba en el paso
    // de confirmación, hay que regenerarla o el botón "Enviar" queda
    // deshabilitado para siempre (depende de previewUrl).
    if (pendingDraft.step === 2) {
      generarPreview(pendingDraft.formValues)
    }
  }

  const empezarDeNuevo = () => {
    clearDraft()
    reset(DEFAULT_VALUES)
    setSoporte(null)
    setDocumentoIdentidad(null)
    setDocumentoFirmado(null)
    setStep(0)
    setPendingDraft(null)
  }

  const generarPreview = (values: SolicitudFormValues) => {
    if (previewUrlRef.current) URL.revokeObjectURL(previewUrlRef.current)
    setPreviewUrl(null)
    preview.mutate(values, {
      onSuccess: (url) => { previewUrlRef.current = url; setPreviewUrl(url) },
    })
  }

  const next = async () => {
    const ok = await trigger(STEP_FIELDS[step])
    if (step === 1 && (medio === 'electoral' || medio === 'sisben') && !soporte) {
      setSoporteError('Debe adjuntar el soporte de acreditación')
      return
    }
    if (step === 1 && !documentoIdentidad) {
      setDocumentoIdentidadError('Debe adjuntar su documento de identidad')
      return
    }
    setSoporteError(undefined)
    setDocumentoIdentidadError(undefined)
    if (!ok) return

    // Al pasar a "Confirmación" se genera la vista previa del PDF con los datos ya diligenciados.
    if (step === 1) generarPreview(getValues())
    setStep((s) => Math.min(s + 1, STEPS.length - 1))
  }

  const submit = () => {
    if (!documentoFirmado) {
      setDocumentoFirmadoError('Debe adjuntar el documento firmado')
      return
    }
    const v = getValues()
    const fd = new FormData()
    Object.entries(v).forEach(([k, val]) => {
      if (val) fd.append(k, val as string)
    })
    if (soporte) fd.append('soporte', soporte)
    if (documentoIdentidad) fd.append('documento_identidad', documentoIdentidad)
    fd.append('documento_firmado', documentoFirmado)
    // Honeypot: campo invisible para personas, si un bot lo rellena el backend rechaza.
    fd.append('sitio_web', '')

    crear.mutate(fd, {
      onSuccess: (data) => { setResult(data); clearDraft() },
    })
  }

  // Todavía no se resolvió si hay o no un borrador guardado — evita un parpadeo del wizard vacío.
  if (!draftChecked) return null

  if (pendingDraft) {
    return (
      <main className="relative min-h-screen w-full overflow-hidden">
        <img src={fondoLogin} alt="" aria-hidden className="absolute inset-0 h-full w-full object-cover" />
        <div
          className="absolute inset-0 bg-gradient-to-br from-[#00031e]/25 via-primary-700/10 to-[#00031e]/30"
          aria-hidden
        />

        <div className="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
          <div className="mx-auto w-full max-w-md">
            <div className="mb-6 flex flex-col items-center text-center text-white">
              <div className="mb-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1.5 ring-2 ring-gold-light ring-offset-2 ring-offset-primary-700">
                <img src={escudo} alt="Escudo Alcaldía de Monterrey" className="h-full w-full rounded-full object-cover" />
              </div>
              <h1 className="text-xl font-bold">Tienes una solicitud sin terminar</h1>
            </div>
            <Card>
              <CardContent className="space-y-4 text-center">
                <p className="text-sm text-institutional-muted">
                  Guardamos el progreso de una solicitud del {new Date(pendingDraft.savedAt).toLocaleString('es-CO')}.
                  ¿Quieres continuar donde quedaste o empezar de nuevo?
                </p>
                <div className="flex flex-col gap-2 sm:flex-row">
                  <Button variant="outline" className="flex-1" onClick={empezarDeNuevo}>Empezar de nuevo</Button>
                  <Button variant="primary" className="flex-1" onClick={continuarBorrador}>Continuar</Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    )
  }

  return (
    <main className="relative min-h-screen w-full overflow-hidden">
      <img src={fondoLogin} alt="" aria-hidden className="absolute inset-0 h-full w-full object-cover" />
      <div
        className="absolute inset-0 bg-gradient-to-br from-[#00031e]/25 via-primary-700/10 to-[#00031e]/30"
        aria-hidden
      />

      <div className="relative z-10 mx-auto max-w-2xl px-4 py-10">
        <div className="mb-6 flex flex-col items-center text-center text-white">
          <div className="mb-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1.5 ring-2 ring-gold-light ring-offset-2 ring-offset-primary-700">
            <img src={escudo} alt="Escudo Alcaldía de Monterrey" className="h-full w-full rounded-full object-cover" />
          </div>
          <h1 className="text-xl font-bold">Solicitud de Certificado de Residencia</h1>
          <p className="text-sm text-white/70">Alcaldía de Monterrey, Casanare · Trámite gratuito, sin necesidad de registrarse</p>
          <div className="mt-3 flex items-center gap-4">
            <Link
              to="/consultar-solicitud"
              className="inline-flex items-center gap-1.5 text-sm font-medium text-gold-light underline-offset-2 hover:underline"
            >
              <Search className="h-4 w-4" /> Consultar mi solicitud
            </Link>
            <Link
              to="/verificar"
              className="inline-flex items-center gap-1.5 text-sm font-medium text-gold-light underline-offset-2 hover:underline"
            >
              <ShieldCheck className="h-4 w-4" /> Verificar residencia
            </Link>
          </div>
        </div>

        {result ? (
          <Card>
            <CardContent className="flex flex-col items-center py-10 text-center">
              <CheckCircle2 className="h-16 w-16 text-success" />
              <h2 className="mt-4 text-2xl font-bold text-institutional-text">¡Solicitud enviada!</h2>
              <p className="mt-1 text-institutional-muted">{result.message}</p>

              <div className="mt-6 w-full rounded-xl border border-institutional-border bg-institutional-bg p-5">
                <p className="text-xs uppercase tracking-wide text-institutional-muted">Referencia de seguimiento</p>
                <p className="mt-1 text-3xl font-bold tracking-tight text-primary">{result.data.referencia}</p>
                <p className="mt-3 text-sm text-institutional-muted">
                  Guarde esta referencia. Su solicitud será radicada por la Ventanilla Única de Registro (VUR) y
                  recibirá el número de radicado oficial en el correo que registró.
                </p>
              </div>

              <Link
                to={`/consultar-solicitud?referencia=${result.data.referencia}`}
                className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary underline-offset-2 hover:underline"
              >
                <Search className="h-4 w-4" /> Consultar el estado de mi solicitud
              </Link>
            </CardContent>
          </Card>
        ) : (
          <>
            <div className="mb-6">
              <Stepper steps={STEPS} current={step} />
            </div>

            <Card>
              <CardContent className="space-y-5">
                {crear.isError && (
                  <div role="alert" className="rounded-lg border border-danger/40 bg-red-50 px-4 py-3 text-sm text-danger">
                    {getApiErrorMessage(crear.error, 'No fue posible enviar la solicitud.')}
                  </div>
                )}

                {step === 0 && <DatosCiudadanoStep register={register} errors={errors} catalogos={catalogos} />}

                {step === 1 && (
                  <div className="space-y-5">
                    <CertificadoSoporteStep
                      register={register}
                      errors={errors}
                      catalogos={catalogos}
                      medio={medio}
                      soporte={soporte}
                      soporteError={soporteError}
                      onSoporteChange={(f) => { setSoporte(f); setSoporteError(undefined) }}
                      soporteInmediato
                    />
                    <Field label="Documento de identidad" htmlFor="documento_identidad" required
                      hint="Cédula, tarjeta de identidad u otro documento que lo identifique">
                      <FileUpload
                        file={documentoIdentidad}
                        onChange={(f) => { setDocumentoIdentidad(f); setDocumentoIdentidadError(undefined) }}
                        error={documentoIdentidadError}
                      />
                    </Field>
                  </div>
                )}

                {step === 2 && (
                  <div className="space-y-5">
                    <p className="text-sm text-institutional-muted">
                      Así quedará su solicitud. Descárguela, imprímala y fírmela a mano — este trámite no maneja
                      firma electrónica. Luego escanee o fotografíe el documento firmado (en PDF) y súbalo abajo.
                    </p>
                    {preview.isPending && (
                      <div className="flex h-[60vh] items-center justify-center rounded-lg border border-institutional-border bg-institutional-bg text-sm text-institutional-muted">
                        Generando vista previa…
                      </div>
                    )}
                    {preview.isError && (
                      <div className="flex h-[60vh] flex-col items-center justify-center gap-2 rounded-lg border border-danger/40 bg-red-50 text-center text-sm text-danger">
                        <FileWarning className="h-8 w-8" />
                        {getApiErrorMessage(preview.error, 'No fue posible generar la vista previa.')}
                      </div>
                    )}
                    {previewUrl && !preview.isPending && (
                      <>
                        <iframe
                          src={previewUrl}
                          title="Vista previa de la solicitud"
                          className="h-[60vh] w-full rounded-lg border border-institutional-border"
                        />
                        <a href={previewUrl} download="solicitud-certificado-residencia.pdf">
                          <Button variant="outline" className="w-full"><Download className="h-4 w-4" /> Descargar para imprimir y firmar</Button>
                        </a>
                      </>
                    )}

                    <Field label="Documento firmado" htmlFor="documento_firmado" required
                      hint="Suba el PDF de la solicitud ya impresa, firmada a mano y escaneada">
                      <FileUpload
                        file={documentoFirmado}
                        accept=".pdf"
                        onChange={(f) => { setDocumentoFirmado(f); setDocumentoFirmadoError(undefined) }}
                        error={documentoFirmadoError}
                      />
                    </Field>
                  </div>
                )}

                <div className="flex items-center justify-between border-t border-institutional-border pt-5">
                  <Button variant="ghost" onClick={() => setStep((s) => Math.max(s - 1, 0))} disabled={step === 0}>
                    <ArrowLeft className="h-4 w-4" /> Atrás
                  </Button>
                  {step < STEPS.length - 1 ? (
                    <Button variant="primary" onClick={next}>
                      Continuar <ArrowRight className="h-4 w-4" />
                    </Button>
                  ) : (
                    <Button variant="success" onClick={submit} loading={crear.isPending} disabled={preview.isPending || !previewUrl || !documentoFirmado}>
                      <Send className="h-4 w-4" /> Enviar solicitud
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          </>
        )}

        <button
          type="button"
          onClick={() => setShowNexGovIA(true)}
          className="mt-6 block w-full text-center text-xs text-white/60 underline-offset-2 transition hover:text-gold-light hover:underline"
        >
          Desarrollado por NexGovIA · Sovereign Data and AI
        </button>
      </div>

      <NexGovIAInfoModal open={showNexGovIA} onClose={() => setShowNexGovIA(false)} />
    </main>
  )
}
