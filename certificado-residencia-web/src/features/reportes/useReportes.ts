import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { ReportesFiltros, ReportesIndicadores } from './types'

function limpiarFiltros(filtros: ReportesFiltros) {
  return Object.fromEntries(
    Object.entries(filtros).filter(([, v]) => v !== undefined && v !== ''),
  )
}

export function useReportes(filtros: ReportesFiltros, enabled: boolean) {
  return useQuery({
    queryKey: ['reportes', filtros],
    enabled,
    queryFn: async () => {
      const { data } = await api.get<ReportesIndicadores>('/reportes', { params: limpiarFiltros(filtros) })
      return data
    },
  })
}

/** Descarga el CSV de radicados con los filtros activos. */
export async function exportarRadicadosCsv(filtros: ReportesFiltros) {
  const { data } = await api.get('/reportes/radicados/export', {
    params: limpiarFiltros(filtros),
    responseType: 'blob',
  })

  const url = URL.createObjectURL(data as Blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `radicados_${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

/** Descarga el reporte gerencial completo en PDF con los filtros activos. */
export async function exportarReportePdf(filtros: ReportesFiltros) {
  const { data } = await api.get('/reportes/export/pdf', {
    params: limpiarFiltros(filtros),
    responseType: 'blob',
  })

  const url = URL.createObjectURL(data as Blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `reporte_${new Date().toISOString().slice(0, 10)}.pdf`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
