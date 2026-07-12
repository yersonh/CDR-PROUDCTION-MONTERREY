import { Link } from 'react-router-dom'
import {
  Clock, FileCheck2, FileText, Loader2, ShieldCheck, Stamp, XCircle,
} from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { StatTile } from '@/components/dashboard/StatTile'
import { BarList, TrendBars, SEMANTIC_HEX } from '@/components/dashboard/BarList'
import { useAuth } from '@/features/auth/useAuth'
import { useDashboard } from './useDashboard'

export function DashboardPage() {
  const { user, hasPermission } = useAuth()
  const puedeVer = hasPermission('dashboard.ver')
  const { data, isLoading } = useDashboard(puedeVer)

  // Ciudadano: pantalla de bienvenida
  if (!puedeVer) {
    return (
      <div className="mx-auto max-w-4xl animate-fade-up">
        <div className="mb-6 flex items-center gap-2 text-success">
          <ShieldCheck className="h-5 w-5" />
          <span className="text-sm font-semibold">Sesión activa</span>
        </div>
        <h1 className="text-2xl font-bold text-institutional-text">Bienvenido, {user?.name}</h1>
        <p className="text-institutional-muted">{user?.email}</p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link to="/solicitudes"><Button variant="outline"><FileText className="h-4 w-4" /> Mis solicitudes</Button></Link>
        </div>
      </div>
    )
  }

  if (isLoading || !data) {
    return <div className="flex justify-center py-20"><Loader2 className="h-8 w-8 animate-spin text-primary" /></div>
  }

  const { resumen, por_estado, por_medio, por_sector, tendencia, bandeja_rol } = data
  const estadoItems = por_estado.filter((e) => e.total > 0).map((e) => ({
    label: e.label, value: e.total, color: SEMANTIC_HEX[e.color],
  }))
  const medioItems = por_medio.map((m) => ({ label: m.label.split(' (')[0], value: m.total }))
  const sectorItems = por_sector.map((s) => ({ label: s.sector, value: s.total }))

  return (
    <div className="animate-fade-up space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-institutional-text">Panel de indicadores</h1>
        <p className="text-institutional-muted">Certificado de Residencia · datos en tiempo real</p>
      </div>

      {/* KPIs principales */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatTile label="Solicitudes" value={resumen.total} icon={FileText} accent="primary" />
        <StatTile label="Certificados" value={resumen.certificados_emitidos} icon={FileCheck2} accent="success" />
        <StatTile label="Pendientes" value={resumen.pendientes} icon={Clock} accent="warning" />
        <StatTile label="Rechazadas" value={resumen.rechazadas} icon={XCircle} accent="danger" />
        <StatTile
          label="Tiempo promedio"
          value={resumen.tiempo_promedio_dias ?? '—'}
          hint="días de respuesta"
          icon={Clock}
          accent="primary"
        />
      </div>

      {/* Bandeja por rol */}
      {Object.keys(bandeja_rol).length > 0 && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {bandeja_rol.pendientes_firma !== undefined && (
            <StatTile label="Pendientes de firma" value={bandeja_rol.pendientes_firma} icon={Stamp} accent="warning" />
          )}
          {bandeja_rol.firmadas_hoy !== undefined && (
            <StatTile label="Firmadas hoy" value={bandeja_rol.firmadas_hoy} icon={FileCheck2} accent="success" />
          )}
          {bandeja_rol.radicadas_hoy !== undefined && (
            <StatTile label="Radicadas hoy" value={bandeja_rol.radicadas_hoy} icon={FileText} accent="primary" />
          )}
          {bandeja_rol.en_validacion !== undefined && (
            <StatTile label="En validación" value={bandeja_rol.en_validacion} icon={Clock} accent="primary" />
          )}
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Solicitudes por estado</CardTitle></CardHeader>
          <CardContent>
            {estadoItems.length ? <BarList items={estadoItems} /> : <Empty />}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Por medio de acreditación</CardTitle></CardHeader>
          <CardContent>
            {medioItems.some((m) => m.value > 0) ? <BarList items={medioItems} /> : <Empty />}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Certificados por mes</CardTitle></CardHeader>
          <CardContent><TrendBars items={tendencia} /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Por barrio, vereda o sector</CardTitle></CardHeader>
          <CardContent>
            {sectorItems.length ? <BarList items={sectorItems} /> : <Empty />}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

function Empty() {
  return <p className="py-6 text-center text-sm text-institutional-muted">Sin datos aún.</p>
}
