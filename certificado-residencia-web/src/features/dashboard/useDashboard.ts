import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'

export interface Indicadores {
  resumen: {
    total: number
    certificados_emitidos: number
    pendientes: number
    rechazadas: number
    tiempo_promedio_dias: number | null
  }
  por_estado: { estado: string; label: string; color: string; total: number }[]
  por_medio: { medio: string; label: string; total: number }[]
  por_sector: { sector: string; total: number }[]
  tendencia: { periodo: string; label: string; total: number }[]
  bandeja_rol: Partial<{
    pendientes_firma: number
    firmadas_hoy: number
    radicadas_hoy: number
    en_validacion: number
  }>
}

export function useDashboard(enabled: boolean) {
  return useQuery({
    queryKey: ['dashboard', 'indicadores'],
    enabled,
    queryFn: async () => {
      const { data } = await api.get<Indicadores>('/dashboard/indicadores')
      return data
    },
  })
}
