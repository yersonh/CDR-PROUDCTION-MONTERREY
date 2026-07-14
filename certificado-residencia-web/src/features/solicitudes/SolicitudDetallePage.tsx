import { Link, useParams } from 'react-router-dom'
import { ArrowLeft, FileText, Loader2, MapPin, Paperclip, User } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { EstadoBadge, SemaforoSla } from '@/components/ui/estado-badge'
import { cn } from '@/lib/utils'
import { useSolicitud, verDocumentoExpediente } from './api'
import { GestionSolicitud } from './GestionSolicitud'
import type { Documento } from './types'

// Concepto del especialista (SISBEN/JAC): no es un anexo aportado por el
// ciudadano, es la respuesta oficial que decide si la solicitud cumple.
const TIPOS_RESPUESTA_ESPECIALISTA = ['respuesta_oficio_sisben', 'certificacion_jac']

const COLOR_DOT: Record<string, string> = {
  blue: 'bg-blue-500', indigo: 'bg-indigo-500', amber: 'bg-amber-500',
  cyan: 'bg-cyan-500', violet: 'bg-violet-500', green: 'bg-green-500', red: 'bg-red-500',
}

export function SolicitudDetallePage() {
  const { id } = useParams()
  const { data: s, isLoading, isError } = useSolicitud(id)

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="h-8 w-8 animate-spin text-white" />
      </div>
    )
  }
  if (isError || !s) {
    return (
      <div className="mx-auto max-w-lg py-20 text-center">
        <p className="text-white/70">No se encontró la solicitud o no tiene acceso.</p>
        <Link to="/solicitudes" className="mt-3 inline-block text-gold-light hover:underline">Volver</Link>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-6xl animate-fade-up">
      <Link to="/solicitudes" className="mb-3 inline-flex items-center gap-1 text-sm text-white/70 hover:text-gold-light">
        <ArrowLeft className="h-4 w-4" /> Volver
      </Link>

      {/* Cabecera */}
      <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-white">{s.radicado}</h1>
          <p className="text-white/70">{s.tipo_certificado.label}</p>
        </div>
        <div className="flex flex-col items-end gap-2">
          <EstadoBadge label={s.estado.label} color={s.estado.color} />
          <SemaforoSla semaforo={s.sla.semaforo} dias={s.sla.dias_habiles_restantes} />
        </div>
      </div>

      {/* Gestión del trámite: a todo el ancho, no en la columna angosta,
          porque sus formularios (p. ej. certificación JAC) necesitan varios
          campos en una sola fila sin que queden apretados. */}
      <div className="mb-6">
        <GestionSolicitud solicitud={s} />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Datos + documentos */}
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row items-center gap-2">
              <User className="h-4 w-4 text-primary" />
              <CardTitle>Datos del solicitante</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              <Info label="Nombre completo" value={s.ciudadano.nombre_completo} />
              <Info label="Identificación" value={`${s.ciudadano.tipo_documento ?? ''} ${s.ciudadano.numero_identificacion}`} />
              <Info label="Correo" value={s.ciudadano.correo} />
              <Info label="Celular" value={s.ciudadano.celular} />
              <Info label="Dirección" value={s.ciudadano.direccion} icon={<MapPin className="h-3.5 w-3.5" />} />
              <Info label="Barrio / vereda / sector" value={s.ciudadano.barrio_vereda_sector} />
              <Info label="Tipo de certificado" value={s.tipo_certificado.label} />
              <Info label="Medio de acreditación" value={s.medio_acreditacion.label} />
              <Info label="Motivo" value={s.motivo ?? '—'} />
              {s.justificacion_especial && (
                <Info label="Justificación especial" value={s.justificacion_especial} className="sm:col-span-2" />
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center gap-2">
              <Paperclip className="h-4 w-4 text-primary" />
              <CardTitle>Expediente {s.expediente?.codigo}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-5">
              {s.expediente && s.expediente.documentos.length > 0 ? (
                <>
                  <DocumentosGrupo
                    titulo="Solicitud"
                    solicitudId={s.id}
                    documentos={s.expediente.documentos.filter((d) => d.tipo === 'solicitud_firmada')}
                  />
                  <DocumentosGrupo
                    titulo="Respuesta del especialista"
                    solicitudId={s.id}
                    documentos={s.expediente.documentos.filter((d) => TIPOS_RESPUESTA_ESPECIALISTA.includes(d.tipo))}
                  />
                  <DocumentosGrupo
                    titulo="Anexos"
                    solicitudId={s.id}
                    documentos={s.expediente.documentos.filter(
                      (d) => d.tipo !== 'solicitud_firmada' && !TIPOS_RESPUESTA_ESPECIALISTA.includes(d.tipo),
                    )}
                  />
                </>
              ) : (
                <p className="text-sm text-institutional-muted">Aún no se han cargado documentos al expediente.</p>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Línea de tiempo */}
        <div className="space-y-6">
          <Card className="h-fit">
            <CardHeader><CardTitle>Línea de tiempo</CardTitle></CardHeader>
            <CardContent>
              <ol className="relative space-y-6 border-l-2 border-institutional-border pl-5">
                {s.seguimientos?.map((t) => (
                  <li key={t.id} className="relative">
                    <span className={cn('absolute -left-[27px] top-0.5 h-3.5 w-3.5 rounded-full ring-4 ring-white', COLOR_DOT[t.color] ?? 'bg-slate-400')} />
                    <p className="text-sm font-semibold text-institutional-text">{t.estado_label}</p>
                    {t.nota && <p className="text-xs text-institutional-muted">{t.nota}</p>}
                    <p className="mt-0.5 text-xs text-institutional-muted">
                      {new Date(t.fecha).toLocaleString('es-CO')}
                      {t.actor && ` · ${t.actor}`}
                    </p>
                  </li>
                ))}
              </ol>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}

function DocumentosGrupo({ titulo, solicitudId, documentos }: { titulo: string; solicitudId: number; documentos: Documento[] }) {
  if (documentos.length === 0) return null

  return (
    <div>
      <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-institutional-muted">{titulo}</p>
      <ul className="space-y-2">
        {documentos.map((d) => (
          <li key={d.id}>
            <button
              type="button"
              onClick={() => verDocumentoExpediente(solicitudId, d.id)}
              className={cn(
                'flex w-full items-center gap-3 rounded-lg border px-4 py-2.5 text-left transition-colors hover:bg-primary-50/40',
                d.vigente ? 'border-institutional-border' : 'border-institutional-border/60 bg-institutional-bg/50 opacity-70',
              )}
            >
              <FileText className={cn('h-5 w-5', d.vigente ? 'text-primary' : 'text-institutional-muted')} />
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-primary hover:underline">{d.nombre_original}</p>
                <p className="text-xs text-institutional-muted">
                  {d.tipo.replaceAll('_', ' ')} · {(d.size / 1024).toFixed(0)} KB · v{d.version}
                  {!d.vigente && ' · reemplazado'}
                </p>
              </div>
              {d.es_certificado && <EstadoBadge label="Certificado" color="green" />}
              {!d.vigente && <EstadoBadge label={`v${d.version}`} color="slate" />}
            </button>
          </li>
        ))}
      </ul>
    </div>
  )
}

function Info({ label, value, icon, className }: { label: string; value: string; icon?: React.ReactNode; className?: string }) {
  return (
    <div className={className}>
      <p className="text-xs uppercase tracking-wide text-institutional-muted">{label}</p>
      <p className="mt-0.5 flex items-center gap-1 text-sm font-medium text-institutional-text">{icon}{value}</p>
    </div>
  )
}
