import { useState } from 'react'
import { History, Search } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { useAuditoria } from './useAuditoria'

const ACCION_COLOR: Record<string, string> = {
  'certificado.emitido': 'bg-green-100 text-green-700',
  'prevalidacion.concepto': 'bg-cyan-100 text-cyan-700',
  'validacion.registrada': 'bg-indigo-100 text-indigo-700',
  'solicitud.cambio_estado': 'bg-blue-100 text-blue-700',
  'solicitud.subsanada': 'bg-amber-100 text-amber-700',
  'documento.versionado': 'bg-slate-100 text-slate-600',
}

const ACCION_LABEL: Record<string, string> = {
  'certificado.emitido': 'Certificado emitido',
  'prevalidacion.concepto': 'Concepto de prevalidación',
  'validacion.registrada': 'Validación registrada',
  'solicitud.cambio_estado': 'Cambio de estado',
  'solicitud.subsanada': 'Solicitud subsanada',
  'documento.versionado': 'Documento versionado',
}

export function AuditoriaPage() {
  const [buscar, setBuscar] = useState('')
  const { data, isLoading } = useAuditoria({ buscar })

  return (
    <div className="animate-fade-up">
      <div className="mb-6">
        <h1 className="flex items-center gap-2 text-2xl font-bold text-white">
          <History className="h-6 w-6 text-gold-light" /> Bitácora de auditoría
        </h1>
        <p className="text-white/70">Trazabilidad total de las acciones del sistema.</p>
      </div>

      <div className="mb-4 max-w-md">
        <div className="relative">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
          <Input className="pl-9" placeholder="Buscar por descripción o IP" value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        </div>
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
                <tr>
                  <th className="px-5 py-3 font-semibold">Fecha</th>
                  <th className="px-5 py-3 font-semibold">Acción</th>
                  <th className="px-5 py-3 font-semibold">Descripción</th>
                  <th className="px-5 py-3 font-semibold">Usuario</th>
                  <th className="px-5 py-3 font-semibold">IP</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && <tr><td colSpan={5} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
                {!isLoading && data?.data.length === 0 && (
                  <tr><td colSpan={5} className="px-5 py-12 text-center text-institutional-muted">Sin eventos registrados.</td></tr>
                )}
                {data?.data.map((a) => (
                  <tr key={a.id} className="hover:bg-primary-50/30">
                    <td className="whitespace-nowrap px-5 py-3 text-xs text-institutional-muted">
                      {new Date(a.fecha).toLocaleString('es-CO')}
                    </td>
                    <td className="px-5 py-3">
                      <span title={a.accion} className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${ACCION_COLOR[a.accion] ?? 'bg-slate-100 text-slate-600'}`}>
                        {ACCION_LABEL[a.accion] ?? a.accion}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-institutional-text">{a.descripcion ?? '—'}</td>
                    <td className="px-5 py-3 text-institutional-muted">{a.usuario ?? 'Sistema'}</td>
                    <td className="px-5 py-3 text-xs text-institutional-muted">{a.ip ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {data && data.meta.total > 0 && (
        <p className="mt-3 text-xs text-institutional-muted">{data.meta.total} evento(s) registrado(s).</p>
      )}
    </div>
  )
}
