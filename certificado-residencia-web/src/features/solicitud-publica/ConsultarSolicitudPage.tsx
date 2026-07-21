import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { CircleDot, Search, ShieldX } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useConsultarSolicitud } from './useConsultarSolicitud'
import { NexGovIAInfoModal } from '@/components/nexgovia-info-modal'
import escudo from '@/assets/logo-alcaldia.png'

export function ConsultarSolicitudPage() {
  const [params, setParams] = useSearchParams()
  const [referencia, setReferencia] = useState(params.get('referencia') ?? '')
  const [consulta, setConsulta] = useState<string | null>(params.get('referencia'))
  const [showNexGovIA, setShowNexGovIA] = useState(false)
  const { data, isFetching } = useConsultarSolicitud(consulta)

  useEffect(() => {
    const r = params.get('referencia')
    if (r) { setReferencia(r); setConsulta(r) }
  }, [params])

  const buscar = () => {
    const r = referencia.trim().toUpperCase()
    if (!r) return
    setParams({ referencia: r })
    setConsulta(r)
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#00031e] via-primary-700 to-[#00031e] px-4 py-10">
      <div className="mx-auto max-w-xl">
        <div className="mb-6 flex flex-col items-center text-center text-white">
          <div className="mb-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1.5 ring-2 ring-gold-light ring-offset-2 ring-offset-primary-700">
            <img src={escudo} alt="Escudo Alcaldía de Monterrey" className="h-full w-full rounded-full object-cover" />
          </div>
          <h1 className="text-xl font-bold">Consultar mi solicitud</h1>
          <p className="text-sm text-white/70">Certificado de Residencia Digital · Alcaldía de Monterrey, Casanare</p>
        </div>

        <Card>
          <CardContent className="space-y-4">
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
                <Input
                  className="pl-9 uppercase"
                  placeholder="Referencia (ej. SP-00000020)"
                  value={referencia}
                  onChange={(e) => setReferencia(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && buscar()}
                />
              </div>
              <Button variant="primary" onClick={buscar} loading={isFetching}>Consultar</Button>
            </div>

            {consulta && !isFetching && data && (
              data.encontrada ? (
                <div className="animate-fade-up space-y-4">
                  <div className="flex items-center gap-3 rounded-xl border border-primary/30 bg-institutional-bg p-4">
                    <CircleDot className="h-8 w-8 shrink-0 text-primary" />
                    <div>
                      <p className="font-bold text-institutional-text">{data.label}</p>
                      <p className="text-sm text-institutional-muted">{data.descripcion}</p>
                    </div>
                  </div>

                  <dl className="divide-y divide-institutional-border rounded-lg border border-institutional-border">
                    {([
                      ['Referencia', data.referencia],
                      ['Solicitante', data.nombre],
                      ['Tipo de certificado', data.tipo_certificado],
                      ['Fecha de solicitud', new Date(data.creado_at).toLocaleDateString('es-CO')],
                      ['Radicado VUR', data.radicado_vur ?? '—'],
                      ['Radicado Alcaldía', data.radicado_cdr ?? '—'],
                    ] as [string, string][]).map(([k, v]) => (
                      <div key={k} className="flex justify-between gap-4 px-4 py-2.5 text-sm">
                        <dt className="text-institutional-muted">{k}</dt>
                        <dd className="text-right font-medium text-institutional-text">{v}</dd>
                      </div>
                    ))}
                  </dl>
                </div>
              ) : (
                <div className="flex items-center gap-3 rounded-xl border border-danger/40 bg-red-50 p-4">
                  <ShieldX className="h-8 w-8 text-danger" />
                  <div>
                    <p className="font-bold text-institutional-text">Solicitud no encontrada</p>
                    <p className="text-sm text-institutional-muted">{data.message}</p>
                  </div>
                </div>
              )
            )}
          </CardContent>
        </Card>

        <button
          type="button"
          onClick={() => setShowNexGovIA(true)}
          className="mt-6 block w-full text-center text-xs text-white/60 underline-offset-2 transition hover:text-gold-light hover:underline"
        >
          Desarrollado por NexGovIA · Sovereign Data and AI
        </button>
      </div>

      <NexGovIAInfoModal open={showNexGovIA} onClose={() => setShowNexGovIA(false)} />
    </div>
  )
}
