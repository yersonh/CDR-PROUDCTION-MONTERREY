import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Catalogos } from '@/features/solicitudes/types'

export function useCatalogos() {
  return useQuery({
    queryKey: ['catalogos'],
    queryFn: async () => {
      const { data } = await api.get<Catalogos>('/catalogos')
      return data
    },
    staleTime: 1000 * 60 * 60, // los catálogos cambian poco
  })
}

/** Catálogos sin autenticación, para el formulario público de solicitud. */
export function usePublicCatalogos() {
  return useQuery({
    queryKey: ['public-catalogos'],
    queryFn: async () => {
      const { data } = await api.get<Catalogos>('/public/catalogos')
      return data
    },
    staleTime: 1000 * 60 * 60,
  })
}
