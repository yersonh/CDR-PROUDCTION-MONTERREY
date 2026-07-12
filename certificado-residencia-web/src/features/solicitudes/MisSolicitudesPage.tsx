import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ArrowUpRight, FileText, Search } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { EstadoBadge, SemaforoSla } from '@/components/ui/estado-badge'
import { useAuth } from '@/features/auth/useAuth'
import { useCatalogos } from '@/features/catalogos/useCatalogos'
import { useSolicitudes } from './api'

// Cada especialista cae directo en las solicitudes de su medio de
// acreditación en vez de la bandeja general — no hay una página dedicada
// nueva, solo un filtro por defecto sobre la lista ya existente.
const MEDIO_POR_ROL: Record<string, string> = {
  funcionario_sisben: 'sisben',
  presidente_jac: 'jac',
}

export function MisSolicitudesPage() {
  const { hasRole } = useAuth()
  const { data: catalogos } = useCatalogos()
  const navigate = useNavigate()
  // Derivado del rol en cada render, no un useState — no hay ningún control
  // en pantalla que lo cambie, y guardarlo en estado con el valor inicial
  // solo se evaluaría una vez: si los datos del usuario cargan un instante
  // después del primer render, el filtro se quedaría vacío para siempre.
  const medioAcreditacion = Object.entries(MEDIO_POR_ROL).find(([rol]) => hasRole(rol))?.[1] ?? ''
  const [buscar, setBuscar] = useState('')
  const [estado, setEstado] = useState('')
  const { data, isLoading } = useSolicitudes({ buscar, estado, medio_acreditacion: medioAcreditacion })

  const titulo = 'Bandeja de solicitudes'

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-institutional-text">{titulo}</h1>
          <p className="text-institutional-muted">Consulte el estado y seguimiento de los trámites.</p>
        </div>
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
                  <th className="px-5 py-3 font-semibold" />
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && (
                  <tr><td colSpan={7} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>
                )}
                {!isLoading && data?.data.length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-5 py-12 text-center">
                      <FileText className="mx-auto mb-2 h-8 w-8 text-institutional-muted/50" />
                      <p className="text-institutional-muted">No hay solicitudes para mostrar.</p>
                    </td>
                  </tr>
                )}
                {data?.data.map((s) => (
                  <tr
                    key={s.id}
                    onClick={() => navigate(`/solicitudes/${s.id}`)}
                    className="cursor-pointer transition-colors hover:bg-primary-50/40"
                  >
                    <td className="px-5 py-3">
                      <Link
                        to={`/solicitudes/${s.id}`}
                        onClick={(e) => e.stopPropagation()}
                        className="font-semibold text-primary hover:underline"
                      >
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
                    <td className="px-5 py-3 text-institutional-muted">
                      {new Date(s.fecha_radicacion).toLocaleString('es-CO', {
                        dateStyle: 'short',
                        timeStyle: 'short',
                      })}
                    </td>
                    <td className="px-5 py-3 text-right">
                      <Link to={`/solicitudes/${s.id}`} onClick={(e) => e.stopPropagation()}>
                        <Button variant="outline">
                          <ArrowUpRight className="h-4 w-4" /> Ver
                        </Button>
                      </Link>
                    </td>
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
