import { useState } from 'react'
import { Link } from 'react-router-dom'
import { FileText, PlusCircle, Search } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { EstadoBadge, SemaforoSla } from '@/components/ui/estado-badge'
import { useAuth } from '@/features/auth/useAuth'
import { useCatalogos } from '@/features/catalogos/useCatalogos'
import { useSolicitudes } from './api'

export function MisSolicitudesPage() {
  const { hasPermission, hasRole } = useAuth()
  const { data: catalogos } = useCatalogos()
  const [buscar, setBuscar] = useState('')
  const [estado, setEstado] = useState('')
  const { data, isLoading } = useSolicitudes({ buscar, estado })

  const titulo = hasRole('ciudadano') ? 'Mis solicitudes' : 'Bandeja de solicitudes'

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-institutional-text">{titulo}</h1>
          <p className="text-institutional-muted">Consulte el estado y seguimiento de los trámites.</p>
        </div>
        {hasPermission('solicitudes.crear') && (
          <Link to="/solicitudes/nueva">
            <Button variant="primary"><PlusCircle className="h-4 w-4" /> Nueva solicitud</Button>
          </Link>
        )}
      </div>

      {/* Filtros */}
      <div className="mb-4 flex flex-wrap gap-3">
        <div className="relative min-w-[220px] flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
          <Input
            className="pl-9"
            placeholder="Buscar por radicado, nombre o identificación"
            value={buscar}
            onChange={(e) => setBuscar(e.target.value)}
          />
        </div>
        <div className="w-full sm:w-56">
          <Select value={estado} onChange={(e) => setEstado(e.target.value)}>
            <option value="">Todos los estados</option>
            {catalogos?.estados.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
          </Select>
        </div>
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
                <tr>
                  <th className="px-5 py-3 font-semibold">Radicado</th>
                  <th className="px-5 py-3 font-semibold">Solicitante</th>
                  <th className="px-5 py-3 font-semibold">Tipo</th>
                  <th className="px-5 py-3 font-semibold">Estado</th>
                  <th className="px-5 py-3 font-semibold">Término</th>
                  <th className="px-5 py-3 font-semibold">Radicación</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && (
                  <tr><td colSpan={6} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>
                )}
                {!isLoading && data?.data.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-5 py-12 text-center">
                      <FileText className="mx-auto mb-2 h-8 w-8 text-institutional-muted/50" />
                      <p className="text-institutional-muted">No hay solicitudes para mostrar.</p>
                    </td>
                  </tr>
                )}
                {data?.data.map((s) => (
                  <tr key={s.id} className="transition-colors hover:bg-primary-50/40">
                    <td className="px-5 py-3">
                      <Link to={`/solicitudes/${s.id}`} className="font-semibold text-primary hover:underline">
                        {s.radicado}
                      </Link>
                    </td>
                    <td className="px-5 py-3">
                      <p className="font-medium text-institutional-text">{s.ciudadano.nombre_completo}</p>
                      <p className="text-xs text-institutional-muted">{s.ciudadano.numero_identificacion}</p>
                    </td>
                    <td className="px-5 py-3 text-institutional-muted">{s.tipo_certificado.label}</td>
                    <td className="px-5 py-3"><EstadoBadge label={s.estado.label} color={s.estado.color} /></td>
                    <td className="px-5 py-3"><SemaforoSla semaforo={s.sla.semaforo} dias={s.sla.dias_habiles_restantes} /></td>
                    <td className="px-5 py-3 text-institutional-muted">{s.fecha_radicacion.slice(0, 10)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {data && data.meta.total > 0 && (
        <p className="mt-3 text-xs text-institutional-muted">
          {data.meta.total} solicitud(es) · página {data.meta.current_page} de {data.meta.last_page}
        </p>
      )}
    </div>
  )
}
