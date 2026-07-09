import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { CheckCircle2, PenLine, Stamp } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { SemaforoSla } from '@/components/ui/estado-badge'
import { useSolicitudes, useFirmar } from '@/features/solicitudes/api'

export function FirmaPage() {
  const { data, isLoading } = useSolicitudes({ estado: 'preaprobada' })
  const firmar = useFirmar()
  const [sel, setSel] = useState<Set<number>>(new Set())
  const [resumen, setResumen] = useState<string | null>(null)

  const solicitudes = data?.data ?? []
  const allSelected = solicitudes.length > 0 && sel.size === solicitudes.length

  const toggle = (id: number) =>
    setSel((prev) => {
      const next = new Set(prev)
      next.has(id) ? next.delete(id) : next.add(id)
      return next
    })

  const toggleAll = () =>
    setSel(allSelected ? new Set() : new Set(solicitudes.map((s) => s.id)))

  const ids = useMemo(() => [...sel], [sel])

  const firmarSeleccionadas = (todas = false) => {
    const target = todas ? solicitudes.map((s) => s.id) : ids
    if (target.length === 0) return
    firmar.mutate(target, {
      onSuccess: (r) => {
        setResumen(r.message)
        setSel(new Set())
      },
    })
  }

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold text-institutional-text">
            <Stamp className="h-6 w-6 text-primary" /> Bandeja de firma
          </h1>
          <p className="text-institutional-muted">Solicitudes preaprobadas listas para firma digital.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => firmarSeleccionadas(false)} disabled={sel.size === 0 || firmar.isPending}>
            <PenLine className="h-4 w-4" /> Firmar seleccionadas ({sel.size})
          </Button>
          <Button variant="success" onClick={() => firmarSeleccionadas(true)} loading={firmar.isPending} disabled={solicitudes.length === 0}>
            <Stamp className="h-4 w-4" /> Firmar todas
          </Button>
        </div>
      </div>

      {resumen && (
        <div className="mb-4 flex items-center gap-2 rounded-lg border border-success/40 bg-green-50 px-4 py-3 text-sm text-success">
          <CheckCircle2 className="h-4 w-4" /> {resumen}
        </div>
      )}
      {firmar.isError && (
        <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-3 text-sm text-danger">
          No fue posible completar la firma.
        </div>
      )}

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
                <tr>
                  <th className="px-5 py-3">
                    <input type="checkbox" checked={allSelected} onChange={toggleAll} aria-label="Seleccionar todas" className="h-4 w-4 accent-[#1b3a6e]" />
                  </th>
                  <th className="px-5 py-3 font-semibold">Radicado</th>
                  <th className="px-5 py-3 font-semibold">Solicitante</th>
                  <th className="px-5 py-3 font-semibold">Tipo</th>
                  <th className="px-5 py-3 font-semibold">Término</th>
                  <th className="px-5 py-3 font-semibold"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && <tr><td colSpan={6} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
                {!isLoading && solicitudes.length === 0 && (
                  <tr><td colSpan={6} className="px-5 py-12 text-center text-institutional-muted">No hay solicitudes pendientes de firma.</td></tr>
                )}
                {solicitudes.map((s) => (
                  <tr key={s.id} className={sel.has(s.id) ? 'bg-primary-50/60' : 'hover:bg-primary-50/30'}>
                    <td className="px-5 py-3">
                      <input type="checkbox" checked={sel.has(s.id)} onChange={() => toggle(s.id)} aria-label={`Seleccionar ${s.radicado}`} className="h-4 w-4 accent-[#1b3a6e]" />
                    </td>
                    <td className="px-5 py-3 font-semibold text-primary">{s.radicado}</td>
                    <td className="px-5 py-3">
                      <p className="font-medium text-institutional-text">{s.ciudadano.nombre_completo}</p>
                      <p className="text-xs text-institutional-muted">{s.ciudadano.numero_identificacion}</p>
                    </td>
                    <td className="px-5 py-3 text-institutional-muted">{s.tipo_certificado.label}</td>
                    <td className="px-5 py-3"><SemaforoSla semaforo={s.sla.semaforo} dias={s.sla.dias_habiles_restantes} /></td>
                    <td className="px-5 py-3 text-right">
                      <Link to={`/solicitudes/${s.id}`} className="text-sm text-primary hover:underline">Ver</Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
