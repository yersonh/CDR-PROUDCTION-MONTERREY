import { useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, CheckCircle2, ClipboardCheck, Download, Gavel, ShieldCheck, Stamp, Upload } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Field } from '@/components/ui/field'
import { FileUpload } from '@/components/ui/file-upload'
import { getApiErrorMessage } from '@/lib/api'
import { useAuth } from '@/features/auth/useAuth'
import { useRegistrarValidacion, usePrevalidar, useFirmar, useSubsanar, descargarCertificadoPdf } from './api'
import type { Solicitud } from './types'

const RESULTADOS = [
  { value: 'cumple', label: 'Cumple requisitos' },
  { value: 'subsanar', label: 'Requiere subsanación' },
  { value: 'rechaza', label: 'Rechazada' },
]

/** Meta de la última prevalidación que pidió subsanación, si la hay (documento solicitado al ciudadano). */
function ultimaSubsanacionSolicitada(solicitud: Solicitud) {
  const prevalidaciones = (solicitud.validaciones ?? []).filter((v) => v.tipo === 'prevalidacion' && v.meta?.tipo_documento_solicitado)
  return prevalidaciones.at(-1)?.meta ?? null
}

export function GestionSolicitud({ solicitud }: { solicitud: Solicitud }) {
  const { hasPermission, hasRole } = useAuth()
  const medio = solicitud.medio_acreditacion.value
  const estado = solicitud.estado.value
  const terminal = estado === 'certificada' || estado === 'rechazada'
  const puedeSubsanar = hasRole('ciudadano') && estado === 'pendiente_soporte'

  const tieneValidacionDe = (tipo: string) => (solicitud.validaciones ?? []).some((v) => v.tipo === tipo)

  // Electoral, SISBEN y JAC solo se validan una vez — una vez registrada la
  // decisión (por Secretaría, el especialista, o la IA en electoral), el
  // formulario debe desaparecer. Si Secretaría no está de acuerdo con lo que
  // ya se registró, corrige desde la prevalidación, no reenviando esto.
  const puedeElectoral = hasPermission('soportes.validar_electoral') && medio === 'electoral' && !tieneValidacionDe('electoral')
  const puedeSisben = hasPermission('soportes.cargar_sisben') && medio === 'sisben' && !tieneValidacionDe('sisben')
  const puedeJac = hasPermission('soportes.cargar_jac') && medio === 'jac' && !tieneValidacionDe('jac')
  // La prevalidación de Secretaría oficializa el concepto de quien validó
  // primero (especialista o, en electoral, la IA vía
  // ValidarCertificadoElectoralConIA) — solo puede verse en "en_validacion".
  // Una vez emitido un concepto, ya se prevalidó (no se repite): si fue
  // "cumple"/"rechaza" la solicitud pasa a un estado que no vuelve a
  // "en_validacion", y si fue "requiere subsanación" queda en
  // "pendiente_soporte" esperando al ciudadano — no debe poder prevalidarse
  // de nuevo hasta que él responda y la solicitud regrese a "en_validacion".
  const puedePrevalidar = hasPermission('validacion.prevalidar') && estado === 'en_validacion'
  const puedeFirmar = hasPermission('firma.firmar') && estado === 'preaprobada'
  const esperandoSubsanacion = hasPermission('validacion.prevalidar') && estado === 'pendiente_soporte' && !puedeSubsanar

  const hayAcciones = puedeElectoral || puedeSisben || puedeJac || puedePrevalidar || puedeFirmar || puedeSubsanar
  const tieneValidaciones = (solicitud.validaciones?.length ?? 0) > 0
  const cert = solicitud.certificado
  const documentoSolicitadoLabel = ultimaSubsanacionSolicitada(solicitud)?.tipo_documento_solicitado_label

  if (terminal && !tieneValidaciones && !cert) return null

  return (
    <Card>
      <CardHeader className="flex flex-row items-center gap-2">
        <ClipboardCheck className="h-4 w-4 text-primary" />
        <CardTitle>Gestión del trámite</CardTitle>
      </CardHeader>
      <CardContent className="space-y-5">
        {cert && <CertificadoBox solicitud={solicitud} />}

        {tieneValidaciones && <ValidacionesList solicitud={solicitud} />}

        {esperandoSubsanacion && (
          <div className="flex items-start gap-2 rounded-lg border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
            <span>
              Esperando la respuesta del ciudadano.
              {documentoSolicitadoLabel && <> Documento solicitado: <strong>{documentoSolicitadoLabel}</strong>.</>}
            </span>
          </div>
        )}

        {hayAcciones && (
          <div className="space-y-5">
            {puedeElectoral && <ElectoralForm solicitud={solicitud} />}
            {puedeSisben && <SoporteForm solicitud={solicitud} tipo="sisben" titulo="Cargar Respuesta de Oficio SISBEN" />}
            {puedeJac && <JacForm solicitud={solicitud} />}
            {puedePrevalidar && <PrevalidarForm solicitud={solicitud} />}
            {puedeFirmar && <FirmaForm solicitud={solicitud} />}
            {puedeSubsanar && <SubsanarForm solicitud={solicitud} />}
          </div>
        )}

        {!hayAcciones && !tieneValidaciones && !cert && (
          <p className="text-sm text-institutional-muted">No hay acciones disponibles para su rol en este trámite.</p>
        )}
      </CardContent>
    </Card>
  )
}

function ValidacionesList({ solicitud }: { solicitud: Solicitud }) {
  return (
    <div>
      <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-institutional-muted">
        Validaciones registradas
      </p>
      <ul className="space-y-2">
        {solicitud.validaciones!.map((v) => (
          <li key={v.id} className="rounded-lg border border-institutional-border px-4 py-2.5 text-sm">
            <div className="flex flex-col gap-1">
              <span className="font-medium capitalize text-institutional-text">{v.tipo.replaceAll('_', ' ')}</span>
              {v.resultado_label && (
                <span className="inline-flex w-fit items-center gap-1 text-xs font-semibold text-primary">
                  <CheckCircle2 className="h-3.5 w-3.5" /> {v.resultado_label}
                </span>
              )}
            </div>
            {v.observacion && <p className="mt-0.5 text-institutional-muted">{v.observacion}</p>}
            {v.meta?.codigo_verificacion && (
              <p className="mt-1 text-xs text-institutional-muted">
                Código: {v.meta.codigo_verificacion} · Presidente: {v.meta.presidente} · Vence: {v.meta.fecha_vencimiento}
              </p>
            )}
            <p className="mt-1 text-xs text-institutional-muted">
              {v.validado_por} · {v.validado_at ? new Date(v.validado_at).toLocaleString('es-CO') : ''}
            </p>
          </li>
        ))}
      </ul>
    </div>
  )
}

function FormError({ error }: { error: unknown }) {
  return (
    <div role="alert" className="rounded-lg border border-danger/40 bg-red-50 px-3 py-2 text-xs text-danger">
      {getApiErrorMessage(error, 'No fue posible completar la acción.')}
    </div>
  )
}

/** Validación del certificado electoral (el ciudadano ya lo cargó). */
function ElectoralForm({ solicitud }: { solicitud: Solicitud }) {
  const registrar = useRegistrarValidacion(solicitud.id)
  const [resultado, setResultado] = useState('cumple')
  const [observacion, setObservacion] = useState('')

  const submit = () => {
    const fd = new FormData()
    fd.append('tipo', 'electoral')
    fd.append('resultado', resultado)
    if (observacion) fd.append('observacion', observacion)
    registrar.mutate(fd)
  }

  return (
    <FormBox titulo="Validar certificado electoral" icon={ClipboardCheck}>
      {registrar.isError && <FormError error={registrar.error} />}
      <Field label="Resultado de la validación" htmlFor="el-res">
        <Select id="el-res" value={resultado} onChange={(e) => setResultado(e.target.value)}>
          {RESULTADOS.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
        </Select>
      </Field>
      <Field label="Observación" htmlFor="el-obs">
        <Textarea id="el-obs" rows={2} value={observacion} onChange={(e) => setObservacion(e.target.value)} placeholder="Autenticidad y consistencia documental" />
      </Field>
      <Button variant="primary" onClick={submit} loading={registrar.isPending}>Registrar validación</Button>
    </FormBox>
  )
}

const RESULTADOS_SOPORTE = [
  { value: 'cumple', label: 'Cumple requisitos' },
  { value: 'rechaza', label: 'No cumple requisitos' },
]

/** Carga de soporte con archivo (SISBEN): el funcionario decide aquí si cumple o no. */
function SoporteForm({ solicitud, tipo, titulo }: { solicitud: Solicitud; tipo: string; titulo: string }) {
  const registrar = useRegistrarValidacion(solicitud.id)
  const [file, setFile] = useState<File | null>(null)
  const [resultado, setResultado] = useState<'cumple' | 'rechaza'>('cumple')
  const [observacion, setObservacion] = useState('')
  const [error, setError] = useState<string>()

  const submit = () => {
    if (!file) { setError('Debe adjuntar la certificación'); return }
    if (resultado === 'rechaza' && !observacion.trim()) { setError('Indique el motivo del no cumplimiento'); return }
    const fd = new FormData()
    fd.append('tipo', tipo)
    fd.append('soporte', file)
    fd.append('resultado', resultado)
    if (observacion) fd.append('observacion', observacion)
    registrar.mutate(fd)
  }

  return (
    <FormBox titulo={titulo} icon={Upload}>
      {registrar.isError && <FormError error={registrar.error} />}
      <FileUpload file={file} onChange={(f) => { setFile(f); setError(undefined) }} error={error} />
      <Field label="Resultado de la validación" htmlFor={`${tipo}-res`}>
        <Select id={`${tipo}-res`} value={resultado}
          onChange={(e) => { setResultado(e.target.value as typeof resultado); setError(undefined) }}>
          {RESULTADOS_SOPORTE.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
        </Select>
      </Field>
      <Field label="Observación" htmlFor={`${tipo}-obs`} required={resultado === 'rechaza'} error={error}>
        <Textarea id={`${tipo}-obs`} rows={2} value={observacion} onChange={(e) => { setObservacion(e.target.value); setError(undefined) }}
          placeholder={resultado === 'cumple' ? 'Antigüedad mínima de 1 año verificada' : 'Motivo obligatorio'} />
      </Field>
      <Button variant={resultado === 'rechaza' ? 'danger' : 'primary'} onClick={submit} loading={registrar.isPending}>
        Cargar certificación
      </Button>
    </FormBox>
  )
}

/**
 * Carga de la certificación JAC. Presidente y sector ya no se escriben a
 * mano: cada Presidente JAC tiene su propio login atado a un sector (ver
 * PresidenteJac en el backend), así que aquí solo se muestran de solo
 * lectura, tomados de la sesión y de la solicitud.
 */
function JacForm({ solicitud }: { solicitud: Solicitud }) {
  const { user } = useAuth()
  const registrar = useRegistrarValidacion(solicitud.id)
  const [file, setFile] = useState<File | null>(null)
  const [f, setF] = useState({ codigo_verificacion: '', fecha_expedicion: '', fecha_vencimiento: '', qr: '' })
  const [error, setError] = useState<string>()
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement>) => setF((p) => ({ ...p, [k]: e.target.value }))

  const submit = () => {
    if (!file) { setError('Debe adjuntar la certificación JAC'); return }
    const fd = new FormData()
    fd.append('tipo', 'jac')
    fd.append('soporte', file)
    fd.append('presidente', user?.name ?? '')
    fd.append('sector', solicitud.sector?.nombre ?? solicitud.ciudadano.barrio_vereda_sector)
    Object.entries(f).forEach(([k, v]) => v && fd.append(k, v))
    registrar.mutate(fd)
  }

  return (
    <FormBox titulo="Cargar certificación JAC" icon={Upload}>
      {registrar.isError && <FormError error={registrar.error} />}
      <FileUpload file={file} onChange={(x) => { setFile(x); setError(undefined) }} error={error} />
      <div className="grid gap-3 sm:grid-cols-2">
        <Field label="Código" htmlFor="jac-cod" required><Input id="jac-cod" value={f.codigo_verificacion} onChange={set('codigo_verificacion')} /></Field>
        <Field label="Presidente" htmlFor="jac-pre"><Input id="jac-pre" value={user?.name ?? ''} disabled /></Field>
        <Field label="Expedición" htmlFor="jac-fe" required><Input id="jac-fe" type="date" value={f.fecha_expedicion} onChange={set('fecha_expedicion')} /></Field>
        <Field label="Vencimiento" htmlFor="jac-fv" required><Input id="jac-fv" type="date" value={f.fecha_vencimiento} onChange={set('fecha_vencimiento')} /></Field>
        <Field label="Sector" htmlFor="jac-sec"><Input id="jac-sec" value={solicitud.sector?.nombre ?? solicitud.ciudadano.barrio_vereda_sector} disabled /></Field>
        <Field label="Código QR" htmlFor="jac-qr"><Input id="jac-qr" value={f.qr} onChange={set('qr')} placeholder="Opcional" /></Field>
      </div>
      <Button variant="primary" onClick={submit} loading={registrar.isPending}>Cargar certificación JAC</Button>
    </FormBox>
  )
}

/** Concepto de prevalidación: Secretaría oficializa cumple, pide subsanar o rechaza. */
function PrevalidarForm({ solicitud }: { solicitud: Solicitud }) {
  const { user } = useAuth()
  const prevalidar = usePrevalidar(solicitud.id)
  const [resultado, setResultado] = useState<'cumple' | 'subsanar' | 'rechaza'>('cumple')
  const [observacion, setObservacion] = useState('')
  const [tipoDocumento, setTipoDocumento] = useState('')
  const [error, setError] = useState<string>()

  // Documentos vigentes del expediente que tiene sentido pedirle corregir al
  // ciudadano (no certificados generados por el sistema).
  const documentosSubsanables = (solicitud.expediente?.documentos ?? []).filter((d) => d.vigente && !d.es_certificado)

  // Quien prevalida "cumple" queda como "Proyectó" en el certificado final
  // — no puede enviarlo al Alcalde sin haber cargado antes su propia firma.
  const requiereFirma = resultado === 'cumple' && !(user?.tiene_firma ?? false)

  const submit = () => {
    if (resultado !== 'cumple' && !observacion.trim()) {
      setError('Indique el motivo de la subsanación o rechazo')
      return
    }
    if (resultado === 'subsanar' && !tipoDocumento) {
      setError('Seleccione cuál documento debe corregir el ciudadano')
      return
    }
    prevalidar.mutate({
      resultado,
      observacion: observacion || undefined,
      tipo_documento: resultado === 'subsanar' ? tipoDocumento : undefined,
    })
  }

  return (
    <FormBox titulo="Prevalidación" icon={Gavel} destacado>
      {prevalidar.isError && <FormError error={prevalidar.error} />}
      {error && <p className="text-xs font-medium text-danger">{error}</p>}
      <Field label="Concepto" htmlFor="pv-res">
        <Select id="pv-res" value={resultado} onChange={(e) => { setResultado(e.target.value as typeof resultado); setError(undefined) }}>
          {RESULTADOS.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
        </Select>
      </Field>
      {resultado === 'subsanar' && (
        <Field label="Documento a corregir" htmlFor="pv-doc" required>
          <Select id="pv-doc" value={tipoDocumento} onChange={(e) => { setTipoDocumento(e.target.value); setError(undefined) }}>
            <option value="">Seleccione…</option>
            {documentosSubsanables.map((d) => <option key={d.tipo} value={d.tipo}>{d.tipo_label}</option>)}
          </Select>
        </Field>
      )}
      <Field label="Observación" htmlFor="pv-obs" required={resultado !== 'cumple'}>
        <Textarea id="pv-obs" rows={2} value={observacion} onChange={(e) => setObservacion(e.target.value)}
          placeholder={resultado === 'cumple' ? 'Opcional' : 'Motivo obligatorio'} />
      </Field>
      {requiereFirma && (
        <div className="flex items-start gap-2 rounded-lg border border-warning/40 bg-amber-50 px-4 py-3 text-sm text-institutional-text">
          <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-warning" />
          <span>
            No tiene firma electrónica registrada. Debe cargarla en{' '}
            <Link to="/perfil" className="font-semibold text-primary hover:underline">Mi perfil</Link>{' '}
            antes de emitir un concepto de "Cumple".
          </span>
        </div>
      )}
      <Button
        variant={resultado === 'rechaza' ? 'danger' : 'success'}
        onClick={submit}
        loading={prevalidar.isPending}
        disabled={requiereFirma}
      >
        Emitir concepto
      </Button>
    </FormBox>
  )
}

/** Subsanación del ciudadano cuando la solicitud está en Pendiente de soporte. */
function SubsanarForm({ solicitud }: { solicitud: Solicitud }) {
  const subsanar = useSubsanar(solicitud.id)
  const documentoLabel = ultimaSubsanacionSolicitada(solicitud)?.tipo_documento_solicitado_label
  const [file, setFile] = useState<File | null>(null)
  const [error, setError] = useState<string>()

  const submit = () => {
    setError(undefined)
    if (!file) { setError('Debe adjuntar el documento solicitado'); return }
    const fd = new FormData()
    fd.append('soporte', file)
    subsanar.mutate(fd)
  }

  return (
    <FormBox titulo="Subsanar solicitud" icon={Upload} destacado>
      {subsanar.isError && <FormError error={subsanar.error} />}
      <p className="text-sm text-institutional-muted">
        Su solicitud requiere subsanación{documentoLabel && <> — debe volver a cargar: <strong>{documentoLabel}</strong></>}.
      </p>
      <FileUpload file={file} onChange={(f) => { setFile(f); setError(undefined) }} error={error} />
      <Button variant="success" onClick={submit} loading={subsanar.isPending}>Enviar subsanación</Button>
    </FormBox>
  )
}

/** Firma y expedición del certificado (Alcalde). */
function FirmaForm({ solicitud }: { solicitud: Solicitud }) {
  const { user } = useAuth()
  const firmar = useFirmar()
  const tieneFirma = user?.tiene_firma ?? false
  const errorLote = firmar.data?.errores[solicitud.radicado]

  if (!tieneFirma) {
    return (
      <FormBox titulo="Firma del Alcalde" icon={Stamp} destacado>
        <div className="flex items-start gap-2 rounded-lg border border-warning/40 bg-amber-50 px-4 py-3 text-sm text-institutional-text">
          <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-warning" />
          <span>
            No tiene firma electrónica registrada. Debe cargarla en{' '}
            <Link to="/perfil" className="font-semibold text-primary hover:underline">Mi perfil</Link>{' '}
            antes de poder firmar certificados.
          </span>
        </div>
      </FormBox>
    )
  }

  return (
    <FormBox titulo="Firma del Alcalde" icon={Stamp} destacado>
      {firmar.isError && <FormError error={firmar.error} />}
      {errorLote && (
        <div className="rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">{errorLote}</div>
      )}
      <p className="text-sm text-institutional-muted">
        Al firmar se generará el certificado oficial con firma electrónica, código QR y hash de integridad,
        y se entregará automáticamente al ciudadano.
      </p>
      <Button
        variant="success"
        className="h-auto w-full whitespace-normal py-2.5 text-center"
        onClick={() => firmar.mutate([solicitud.id])}
        loading={firmar.isPending}
      >
        <Stamp className="h-4 w-4 shrink-0" /> Firmar y expedir certificado
      </Button>
    </FormBox>
  )
}

/** Certificado emitido: descarga y verificación pública. */
function CertificadoBox({ solicitud }: { solicitud: Solicitud }) {
  const cert = solicitud.certificado!
  const [descargando, setDescargando] = useState(false)
  const verificarUrl = `/verificar?codigo=${cert.codigo_verificacion}`

  const descargar = async () => {
    setDescargando(true)
    try {
      await descargarCertificadoPdf(solicitud.id, cert.consecutivo)
    } finally {
      setDescargando(false)
    }
  }

  return (
    <div className="rounded-xl border border-success/40 bg-green-50/60 p-4">
      <p className="mb-2 flex items-center gap-2 text-sm font-semibold text-success">
        <ShieldCheck className="h-4 w-4" /> Certificado expedido
      </p>
      <dl className="grid grid-cols-2 gap-2 text-sm">
        <div><dt className="text-xs text-institutional-muted">Consecutivo</dt><dd className="font-semibold text-institutional-text">{cert.consecutivo}</dd></div>
        <div><dt className="text-xs text-institutional-muted">Código</dt><dd className="font-semibold text-institutional-text">{cert.codigo_verificacion}</dd></div>
        <div><dt className="text-xs text-institutional-muted">Vigente</dt><dd className="font-medium">{cert.vigente ? 'Sí' : 'No'}</dd></div>
        <div><dt className="text-xs text-institutional-muted">Vigencia</dt><dd className="font-medium">{cert.vigencia_hasta?.slice(0, 10)}</dd></div>
      </dl>
      <div className="mt-3 flex flex-wrap gap-2">
        <Button variant="primary" size="sm" onClick={descargar} loading={descargando}>
          <Download className="h-4 w-4" /> Descargar PDF
        </Button>
        <a href={verificarUrl} target="_blank" rel="noreferrer">
          <Button variant="outline" size="sm"><ShieldCheck className="h-4 w-4" /> Verificar</Button>
        </a>
      </div>
    </div>
  )
}

function FormBox({ titulo, icon: Icon, destacado, children }: { titulo: string; icon: React.ElementType; destacado?: boolean; children: React.ReactNode }) {
  return (
    <div className={destacado ? 'rounded-xl border border-primary-100 bg-primary-50/40 p-4' : 'rounded-xl border border-institutional-border p-4'}>
      <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-institutional-text">
        <Icon className="h-4 w-4 text-primary" /> {titulo}
      </p>
      <div className="space-y-3">{children}</div>
    </div>
  )
}
