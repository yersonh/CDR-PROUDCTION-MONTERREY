import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { AlertTriangle, Download, Search, ShieldCheck, ShieldX } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useVerificar } from './useVerificar'
import { NexGovIAInfoModal } from '@/components/nexgovia-info-modal'
import escudo from '@/assets/logo-alcaldia.png'

export function VerificarPage() {
  const [params, setParams] = useSearchParams()
  const [codigo, setCodigo] = useState(params.get('codigo') ?? '')
  const [consulta, setConsulta] = useState<string | null>(params.get('codigo'))
  const [showNexGovIA, setShowNexGovIA] = useState(false)
  const { data, isFetching } = useVerificar(consulta)

  useEffect(() => {
    const c = params.get('codigo')
    if (c) { setCodigo(c); setConsulta(c) }
  }, [params])

  const buscar = () => {
    const c = codigo.trim().toUpperCase()
    if (!c) return
    setParams({ codigo: c })
    setConsulta(c)
  }

  const cert = data?.certificado
  const apiUrl = import.meta.env.VITE_API_URL

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#00031e] via-primary-700 to-[#00031e] px-4 py-10">
      <div className="mx-auto max-w-xl">
        {/* Encabezado institucional */}
        <div className="mb-6 flex flex-col items-center text-center text-white">
          <div className="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white/95 p-1.5">
            <img src={escudo} alt="Escudo Alcaldía de Monterrey" className="h-full w-full object-contain" />
          </div>
          <h1 className="text-xl font-bold">Verificación de autenticidad</h1>
          <p className="text-sm text-white/70">Certificado de Residencia Digital · Alcaldía de Monterrey, Casanare</p>
        </div>

        <Card>
          <CardContent className="space-y-4">
            <div className="flex gap-2">
              <div className="relative flex-1">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
                <Input
                  className="pl-9 uppercase"
                  placeholder="Código de verificación (ej. ABCD-1234)"
                  value={codigo}
                  onChange={(e) => setCodigo(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && buscar()}
                />
              </div>
              <Button variant="primary" onClick={buscar} loading={isFetching}>Verificar</Button>
            </div>

            {consulta && !isFetching && data && (
              data.valido && cert ? (
                <div className="animate-fade-up space-y-4">
                  <div className={`flex items-center gap-3 rounded-xl border p-4 ${data.vigente ? 'border-success/40 bg-green-50' : 'border-amber-300 bg-amber-50'}`}>
                    {data.vigente ? <ShieldCheck className="h-8 w-8 text-success" /> : <AlertTriangle className="h-8 w-8 text-warning" />}
                    <div>
                      <p className="font-bold text-institutional-text">
                        Certificado {data.vigente ? 'válido y vigente' : 'válido (fuera de vigencia)'}
                      </p>
                      <p className="text-sm text-institutional-muted">Documento auténtico expedido por la autoridad competente.</p>
                    </div>
                  </div>

                  <dl className="divide-y divide-institutional-border rounded-lg border border-institutional-border">
                    {([
                      ['Consecutivo', cert.consecutivo],
                      ['Estado', cert.estado],
                      ['Tipo', cert.tipo],
                      ['Radicado', cert.radicado],
                      ['Titular', cert.ciudadano],
                      ['Identificación', cert.identificacion],
                      ['Autoridad emisora', cert.autoridad],
                      ['Firmado por', cert.firmado_por ?? '—'],
                      ['Fecha de expedición', cert.fecha_expedicion?.slice(0, 10)],
                      ['Vigencia hasta', cert.vigencia_hasta?.slice(0, 10)],
                    ] as [string, string][]).map(([k, v]) => (
                      <div key={k} className="flex justify-between gap-4 px-4 py-2.5 text-sm">
                        <dt className="text-institutional-muted">{k}</dt>
                        <dd className="text-right font-medium text-institutional-text">{v}</dd>
                      </div>
                    ))}
                  </dl>

                  <p className="break-all rounded-lg bg-institutional-bg px-3 py-2 text-xs text-institutional-muted">
                    <strong>SHA-256:</strong> {cert.hash_documento}
                  </p>

                  <a href={`${apiUrl}/public/certificados/${cert.codigo_verificacion}/pdf`} target="_blank" rel="noreferrer">
                    <Button variant="primary" className="w-full"><Download className="h-4 w-4" /> Descargar certificado</Button>
                  </a>
                </div>
              ) : (
                <div className="flex items-center gap-3 rounded-xl border border-danger/40 bg-red-50 p-4">
                  <ShieldX className="h-8 w-8 text-danger" />
                  <div>
                    <p className="font-bold text-institutional-text">Certificado no encontrado</p>
                    <p className="text-sm text-institutional-muted">{data.message ?? 'Verifique el código e intente nuevamente.'}</p>
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
