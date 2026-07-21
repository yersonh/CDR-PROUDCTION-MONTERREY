import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { ReportesVurCatalogos, ReportesVurFiltros, ReportesVurIndicadores } from './types'

function limpiarFiltros(filtros: ReportesVurFiltros) {
  return Object.fromEntries(
    Object.entries(filtros).filter(([, v]) => v !== undefined && v !== ''),
  )
}

export function useReportesVur(filtros: ReportesVurFiltros, enabled: boolean) {
  return useQuery({
    queryKey: ['reportes-vur', filtros],
    enabled,
    queryFn: async () => {
      const { data } = await api.get<ReportesVurIndicadores>('/reportes/vur', { params: limpiarFiltros(filtros) })
      return data
    },
  })
}

export function useReportesVurCatalogos() {
  return useQuery({
    queryKey: ['reportes-vur-catalogos'],
    queryFn: async () => {
      const { data } = await api.get<ReportesVurCatalogos>('/reportes/vur/catalogos')
      return data
    },
    staleTime: 10 * 60 * 1000,
  })
}

/** Descarga el CSV detallado de VUR con los filtros activos. */
export async function exportarReporteVurCsv(filtros: ReportesVurFiltros) {
  const { data } = await api.get('/reportes/vur/export/csv', {
    params: limpiarFiltros(filtros),
    responseType: 'blob',
  })

  const url = URL.createObjectURL(data as Blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `radicados_vur_${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

/** Descarga el reporte gerencial completo de VUR en PDF con los filtros activos. */
export async function exportarReporteVurPdf(filtros: ReportesVurFiltros) {
  const { data } = await api.get('/reportes/vur/export/pdf', {
    params: limpiarFiltros(filtros),
    responseType: 'blob',
  })

  const url = URL.createObjectURL(data as Blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `reporte_vur_${new Date().toISOString().slice(0, 10)}.pdf`
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}
