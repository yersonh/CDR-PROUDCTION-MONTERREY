import { useState } from 'react'
import { Download, FileText, Loader2, RotateCcw } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { StatTile } from '@/components/dashboard/StatTile'
import { SEMANTIC_HEX } from '@/components/dashboard/BarList'
import { AnimatedBarChart } from '@/components/dashboard/AnimatedBarChart'
import { AnimatedAreaChart } from '@/components/dashboard/AnimatedAreaChart'
import { useCatalogos } from '@/features/catalogos/useCatalogos'
import { useReportes, exportarRadicadosCsv, exportarReportePdf } from './useReportes'
import { useReportesVur, useReportesVurCatalogos, exportarReporteVurCsv, exportarReporteVurPdf } from './useReportesVur'
import type { ReportesFiltros, ReportesVurFiltros } from './types'

export function ReportesPage() {
  const [tab, setTab] = useState<'cdr' | 'vur'>('cdr')

  return (
    <div className="animate-fade-up space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-white">Reportes</h1>
        <p className="text-white/70">Cumplimiento de SLA, productividad y tendencias por rango de fecha</p>
      </div>

      <div className="flex gap-2 rounded-xl border border-white/15 bg-white/5 p-1">
        <button
          type="button"
          onClick={() => setTab('cdr')}
          className={`flex-1 rounded-lg py-2 text-sm font-semibold transition ${tab === 'cdr' ? 'bg-white text-institutional-text shadow' : 'text-white/70 hover:text-white'}`}
        >
          Certificado de Residencia
        </button>
        <button
          type="button"
          onClick={() => setTab('vur')}
          className={`flex-1 rounded-lg py-2 text-sm font-semibold transition ${tab === 'vur' ? 'bg-white text-institutional-text shadow' : 'text-white/70 hover:text-white'}`}
        >
          VUR — Correspondencia general
        </button>
      </div>

      {tab === 'cdr' ? <ReportesCdr /> : <ReportesVur />}
    </div>
  )
}

function ReportesCdr() {
  const [filtros, setFiltros] = useState<ReportesFiltros>({})
  const [exportandoCsv, setExportandoCsv] = useState(false)
  const [exportandoPdf, setExportandoPdf] = useState(false)
  const { data: catalogos } = useCatalogos()
  const { data, isLoading } = useReportes(filtros, true)

  const actualizar = (cambios: Partial<ReportesFiltros>) => setFiltros((f) => ({ ...f, ...cambios }))
  const limpiar = () => setFiltros({})

  const exportarCsv = async () => {
    setExportandoCsv(true)
    try {
      await exportarRadicadosCsv(filtros)
    } finally {
      setExportandoCsv(false)
    }
  }

  const exportarPdf = async () => {
    setExportandoPdf(true)
    try {
      await exportarReportePdf(filtros)
    } finally {
      setExportandoPdf(false)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap justify-end gap-2">
        <Button variant="outline" onClick={exportarCsv} loading={exportandoCsv}>
          <Download className="h-4 w-4" /> Radicados (CSV)
        </Button>
        <Button variant="gold" onClick={exportarPdf} loading={exportandoPdf}>
          <FileText className="h-4 w-4" /> Exportar reporte (PDF)
        </Button>
      </div>

      {/* Filtros */}
      <Card>
        <CardContent className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-6">
          <div>
            <Label htmlFor="desde">Desde</Label>
            <Input id="desde" type="date" value={filtros.desde ?? ''} onChange={(e) => actualizar({ desde: e.target.value })} />
          </div>
          <div>
            <Label htmlFor="hasta">Hasta</Label>
            <Input id="hasta" type="date" value={filtros.hasta ?? ''} onChange={(e) => actualizar({ hasta: e.target.value })} />
          </div>
          <div>
            <Label htmlFor="dependencia">Dependencia</Label>
            <Select
              id="dependencia"
              value={filtros.dependencia_id ?? ''}
              onChange={(e) => actualizar({ dependencia_id: e.target.value ? Number(e.target.value) : undefined })}
            >
              <option value="">Todas</option>
              {catalogos?.dependencias.map((d) => (
                <option key={d.id} value={d.id}>{d.nombre}</option>
              ))}
            </Select>
          </div>
          <div>
            <Label htmlFor="estado">Estado</Label>
            <Select id="estado" value={filtros.estado ?? ''} onChange={(e) => actualizar({ estado: e.target.value || undefined })}>
              <option value="">Todos</option>
              {catalogos?.estados.map((e) => (
                <option key={e.value} value={e.value}>{e.label}</option>
              ))}
            </Select>
          </div>
          <div>
            <Label htmlFor="medio">Medio de acreditación</Label>
            <Select
              id="medio"
              value={filtros.medio_acreditacion ?? ''}
              onChange={(e) => actualizar({ medio_acreditacion: e.target.value || undefined })}
            >
              <option value="">Todos</option>
              {catalogos?.medios_acreditacion.map((m) => (
                <option key={m.value} value={m.value}>{m.label}</option>
              ))}
            </Select>
          </div>
          <div className="flex items-end">
            <Button variant="outline" className="w-full" onClick={limpiar}>
              <RotateCcw className="h-4 w-4" /> Limpiar filtros
            </Button>
          </div>
        </CardContent>
      </Card>

      {isLoading || !data ? (
        <div className="flex justify-center py-20"><Loader2 className="h-8 w-8 animate-spin text-white" /></div>
      ) : (
        <ContenidoCdr data={data} />
      )}
    </div>
  )
}

function ContenidoCdr({ data }: { data: NonNullable<ReturnType<typeof useReportes>['data']> }) {
  const { resumen, sla, por_estado, por_medio, por_dependencia, tendencia, productividad, rechazos_recientes, vur } = data

  const estadoItems = por_estado.filter((e) => e.total > 0).map((e) => ({
    label: e.label, value: e.total, color: SEMANTIC_HEX[e.color],
  }))
  const medioItems = por_medio.map((m) => ({ label: m.label.split(' (')[0], value: m.total }))
  const dependenciaItems = por_dependencia.slice(0, 6).map((d) => ({ label: d.nombre, value: d.total }))

  return (
    <div className="space-y-6">
      {/* KPIs principales */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatTile label="Solicitudes" value={resumen.total} accent="primary" />
        <StatTile label="Certificadas" value={resumen.certificadas} accent="success" />
        <StatTile label="Pendientes" value={resumen.pendientes} accent="warning" />
        <StatTile label="Rechazadas" value={resumen.rechazadas} accent="danger" />
        <StatTile label="Tiempo promedio" value={resumen.tiempo_promedio_dias ?? '—'} hint="días de respuesta" accent="primary" />
      </div>

      {/* SLA */}
      <Card>
        <CardHeader><CardTitle>Cumplimiento de SLA (15 días hábiles)</CardTitle></CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <StatTile label="En verde" value={sla.verde} accent="success" hint="más de 5 días hábiles" />
          <StatTile label="En ámbar" value={sla.ambar} accent="warning" hint="5 días hábiles o menos" />
          <StatTile label="En rojo" value={sla.rojo} accent="danger" hint="menos de 2 días hábiles" />
          <StatTile label="Vencidas" value={sla.vencidas} accent="danger" hint="fuera de plazo, aún activas" />
          <StatTile
            label="% emitidos a tiempo"
            value={sla.cumplimiento_pct !== null ? `${sla.cumplimiento_pct}%` : '—'}
            accent="primary"
            hint="certificados dentro del plazo"
          />
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Solicitudes por estado</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={estadoItems} /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Por medio de acreditación</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={medioItems} defaultColor="#c8a800" /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Radicados en el tiempo</CardTitle></CardHeader>
          <CardContent><AnimatedAreaChart data={tendencia.map((t) => ({ label: t.label, total: t.total }))} /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Top dependencias</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={dependenciaItems} defaultColor="#2b5ba8" /></CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Productividad por funcionario</CardTitle></CardHeader>
          <CardContent>
            {productividad.length ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-institutional-border text-left text-xs uppercase text-institutional-muted">
                    <th className="py-2">Funcionario</th>
                    <th className="py-2 text-right">Validaciones</th>
                    <th className="py-2 text-right">Firmas</th>
                    <th className="py-2 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {productividad.map((p) => (
                    <tr key={p.usuario_id} className="border-b border-institutional-border last:border-0">
                      <td className="py-2 text-institutional-text">{p.nombre}</td>
                      <td className="py-2 text-right tabular-nums">{p.validaciones}</td>
                      <td className="py-2 text-right tabular-nums">{p.firmas}</td>
                      <td className="py-2 text-right font-semibold tabular-nums">{p.total}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : <Empty />}
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Rechazos recientes</CardTitle></CardHeader>
          <CardContent>
            {rechazos_recientes.length ? (
              <ul className="space-y-3 text-sm">
                {rechazos_recientes.map((r) => (
                  <li key={r.radicado} className="border-b border-institutional-border pb-2 last:border-0">
                    <p className="font-semibold text-institutional-text">{r.radicado} · {r.nombre_completo}</p>
                    <p className="text-xs text-institutional-muted">{r.fecha_radicacion} — {r.motivo ?? 'Sin motivo registrado'}</p>
                  </li>
                ))}
              </ul>
            ) : <Empty />}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Integración VUR</CardTitle></CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-3">
          <StatTile label="Recibidos" value={vur.recibidos} accent="primary" />
          <StatTile label="Radicados" value={vur.radicados} accent="success" />
          <StatTile label="Pendientes por radicar" value={vur.pendientes} accent="warning" />
        </CardContent>
      </Card>
    </div>
  )
}

function ReportesVur() {
  const [filtros, setFiltros] = useState<ReportesVurFiltros>({})
  const [exportandoCsv, setExportandoCsv] = useState(false)
  const [exportandoPdf, setExportandoPdf] = useState(false)
  const { data: catalogosCdr } = useCatalogos()
  const { data: catalogosVur } = useReportesVurCatalogos()
  const { data, isLoading, isError } = useReportesVur(filtros, true)

  const actualizar = (cambios: Partial<ReportesVurFiltros>) => setFiltros((f) => ({ ...f, ...cambios }))
  const limpiar = () => setFiltros({})

  const exportarCsv = async () => {
    setExportandoCsv(true)
    try {
      await exportarReporteVurCsv(filtros)
    } finally {
      setExportandoCsv(false)
    }
  }

  const exportarPdf = async () => {
    setExportandoPdf(true)
    try {
      await exportarReporteVurPdf(filtros)
    } finally {
      setExportandoPdf(false)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap justify-end gap-2">
        <Button variant="outline" onClick={exportarCsv} loading={exportandoCsv}>
          <Download className="h-4 w-4" /> Radicados VUR (CSV)
        </Button>
        <Button variant="gold" onClick={exportarPdf} loading={exportandoPdf}>
          <FileText className="h-4 w-4" /> Exportar reporte VUR (PDF)
        </Button>
      </div>

      {/* Filtros */}
      <Card>
        <CardContent className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
          <div>
            <Label htmlFor="vur-desde">Desde</Label>
            <Input id="vur-desde" type="date" value={filtros.desde ?? ''} onChange={(e) => actualizar({ desde: e.target.value })} />
          </div>
          <div>
            <Label htmlFor="vur-hasta">Hasta</Label>
            <Input id="vur-hasta" type="date" value={filtros.hasta ?? ''} onChange={(e) => actualizar({ hasta: e.target.value })} />
          </div>
          <div>
            <Label htmlFor="vur-dependencia">Dependencia destino</Label>
            <Select
              id="vur-dependencia"
              value={filtros.dependencia_destino_id ?? ''}
              onChange={(e) => actualizar({ dependencia_destino_id: e.target.value ? Number(e.target.value) : undefined })}
            >
              <option value="">Todas</option>
              {catalogosCdr?.dependencias.map((d) => (
                <option key={d.id} value={d.id}>{d.nombre}</option>
              ))}
            </Select>
          </div>
          <div>
            <Label htmlFor="vur-estado">Estado</Label>
            <Select
              id="vur-estado"
              value={filtros.estado_id ?? ''}
              onChange={(e) => actualizar({ estado_id: e.target.value ? Number(e.target.value) : undefined })}
            >
              <option value="">Todos</option>
              {(catalogosVur?.estados ?? []).map((e) => (
                <option key={e.id} value={e.id}>{e.descripcion}</option>
              ))}
            </Select>
          </div>
          <div>
            <Label htmlFor="vur-tipo">Tipo de correspondencia</Label>
            <Select
              id="vur-tipo"
              value={filtros.tipo_correspondencia_id ?? ''}
              onChange={(e) => actualizar({ tipo_correspondencia_id: e.target.value ? Number(e.target.value) : undefined })}
            >
              <option value="">Todos</option>
              {(catalogosVur?.tipos_correspondencia ?? []).map((t) => (
                <option key={t.id} value={t.id}>{t.descripcion}</option>
              ))}
            </Select>
          </div>
          <div className="flex items-end">
            <Button variant="outline" className="w-full" onClick={limpiar}>
              <RotateCcw className="h-4 w-4" /> Limpiar filtros
            </Button>
          </div>
        </CardContent>
      </Card>

      {isError ? (
        <p className="py-10 text-center text-white/80">No fue posible consultar los reportes de VUR en este momento.</p>
      ) : isLoading || !data ? (
        <div className="flex justify-center py-20"><Loader2 className="h-8 w-8 animate-spin text-white" /></div>
      ) : (
        <ContenidoVur data={data} />
      )}
    </div>
  )
}

function ContenidoVur({ data }: { data: NonNullable<ReturnType<typeof useReportesVur>['data']> }) {
  const { kpis, sla, por_estado, por_tipo, por_dependencia, por_operador, por_funcionario, por_medio_ingreso, serie_tiempo } = data

  const estadoItems = por_estado.filter((e) => e.total > 0).map((e) => ({
    label: e.descripcion, value: e.total, color: e.color_hex,
  }))
  const tipoItems = [...por_tipo].sort((a, b) => b.total - a.total).slice(0, 8).map((t) => ({
    label: t.descripcion, value: t.total,
  }))
  const dependenciaItems = por_dependencia.slice(0, 6).map((d) => ({ label: d.nombre, value: d.total }))
  const operadorItems = por_operador.slice(0, 6).map((o) => ({ label: o.nombre, value: o.total }))

  return (
    <div className="space-y-6">
      {/* KPIs principales */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatTile label="Radicados" value={kpis.total} accent="primary" />
        <StatTile label="Respondidos" value={kpis.radicados_respondidos} accent="success" />
        <StatTile label="Vencidos" value={kpis.vencidos} accent="danger" />
        <StatTile label="Tiempo promedio" value={kpis.promedio_dias_respuesta ?? '—'} hint="días de respuesta" accent="primary" />
      </div>

      {/* SLA */}
      <Card>
        <CardHeader><CardTitle>Cumplimiento de SLA</CardTitle></CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <StatTile label="A tiempo" value={sla.respondidos_a_tiempo} accent="success" />
          <StatTile label="Fuera de plazo" value={sla.respondidos_fuera_plazo} accent="danger" />
          <StatTile label="Pendientes vencidos" value={sla.pendientes_vencidos} accent="danger" />
          <StatTile label="Pendientes en plazo" value={sla.pendientes_en_plazo} accent="warning" />
          <StatTile
            label="% a tiempo"
            value={sla.cumplimiento_pct !== null ? `${sla.cumplimiento_pct}%` : '—'}
            accent="primary"
            hint="de los ya respondidos"
          />
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Radicados por estado</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={estadoItems} /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Top tipos de correspondencia</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={tipoItems} defaultColor="#c8a800" /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Radicados en el tiempo</CardTitle></CardHeader>
          <CardContent><AnimatedAreaChart data={serie_tiempo.map((s) => ({ label: s.fecha.slice(5), total: s.total }))} /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Top dependencias</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={dependenciaItems} defaultColor="#2b5ba8" /></CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Por operador</CardTitle></CardHeader>
          <CardContent><AnimatedBarChart items={operadorItems} defaultColor="#8b5cf6" /></CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Top funcionarios destino</CardTitle></CardHeader>
          <CardContent>
            {por_funcionario.length ? (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-institutional-border text-left text-xs uppercase text-institutional-muted">
                    <th className="py-2">Funcionario</th>
                    <th className="py-2 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {por_funcionario.map((f) => (
                    <tr key={f.funcionario_id} className="border-b border-institutional-border last:border-0">
                      <td className="py-2 text-institutional-text">{f.nombre}</td>
                      <td className="py-2 text-right font-semibold tabular-nums">{f.total}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : <Empty />}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Por medio de ingreso</CardTitle></CardHeader>
        <CardContent>
          {por_medio_ingreso.length ? (
            <AnimatedBarChart items={por_medio_ingreso.map((m) => ({ label: m.descripcion, value: m.total }))} defaultColor="#16a34a" />
          ) : <Empty />}
        </CardContent>
      </Card>
    </div>
  )
}

function Empty() {
  return <p className="py-6 text-center text-sm text-institutional-muted">Sin datos para los filtros seleccionados.</p>
}
