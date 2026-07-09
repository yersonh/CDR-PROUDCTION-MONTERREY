import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Paginated } from '@/features/solicitudes/types'

export interface Auditoria {
  id: number
  accion: string
  descripcion: string | null
  usuario: string | null
  entidad: string | null
  entidad_id: number | null
  ip: string | null
  navegador: string | null
  metodo: string | null
  fecha: string
}

export function useAuditoria(filters: { buscar?: string; accion?: string } = {}) {
  return useQuery({
    queryKey: ['auditoria', filters],
    queryFn: async () => {
      const { data } = await api.get<Paginated<Auditoria>>('/auditoria', {
        params: { buscar: filters.buscar || undefined, accion: filters.accion || undefined },
      })
      return data
    },
  })
}
