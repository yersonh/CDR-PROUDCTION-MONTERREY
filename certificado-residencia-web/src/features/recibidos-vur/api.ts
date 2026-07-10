import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Paginated } from '@/features/solicitudes/types'
import type { RecibidoVur } from './types'

export interface RecibidoVurFilters {
  estado?: string
  page?: number
}

export function useRecibidosVur(filters: RecibidoVurFilters = {}) {
  return useQuery({
    queryKey: ['recibidos-vur', filters],
    queryFn: async () => {
      const { data } = await api.get<Paginated<RecibidoVur>>('/recibidos-vur', {
        params: { estado: filters.estado || undefined, page: filters.page },
      })
      return data
    },
  })
}

/** Abre el PDF del recibido en una pestaña nueva (autenticado, vía blob). */
export async function verRecibidoVurPdf(id: number) {
  const res = await api.get(`/recibidos-vur/${id}/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(res.data as Blob)
  window.open(url, '_blank', 'noopener')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}
