import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Notificacion } from './types'

// Mismo patrón que la campanita de VUR: pollear solo el contador (barato),
// y traer la lista completa recién al abrir el panel.
const POLL_INTERVAL_MS = 20_000

export function useNotificacionesNoLeidas() {
  return useQuery({
    queryKey: ['notificaciones-no-leidas'],
    queryFn: async () => {
      const { data } = await api.get<{ no_leidas: number }>('/notificaciones/no-leidas')
      return data.no_leidas
    },
    refetchInterval: POLL_INTERVAL_MS,
    refetchIntervalInBackground: true,
  })
}

export function useNotificaciones(enabled: boolean) {
  return useQuery({
    queryKey: ['notificaciones'],
    enabled,
    queryFn: async () => {
      const { data } = await api.get<{ data: Notificacion[] }>('/notificaciones')
      return data.data
    },
  })
}

export function useMarcarNotificacionLeida() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id: number) => {
      await api.patch(`/notificaciones/${id}/leer`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificaciones-no-leidas'] })
      queryClient.invalidateQueries({ queryKey: ['notificaciones'] })
    },
  })
}

export function useMarcarTodasLeidas() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async () => {
      await api.patch('/notificaciones/leer-todas')
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificaciones-no-leidas'] })
      queryClient.invalidateQueries({ queryKey: ['notificaciones'] })
    },
  })
}
