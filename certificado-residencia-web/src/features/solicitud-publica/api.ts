import { useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { SolicitudFormValues } from '@/features/solicitudes/solicitud-schema'

export interface CreateSolicitudPublicaResult {
  data: { referencia: string; estado: string }
  message: string
}

/** Envío del formulario público (sin autenticación) hacia el endpoint que arma el PDF y lo remite a VUR. */
export function useCreateSolicitudPublica() {
  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await api.post<CreateSolicitudPublicaResult>('/public/solicitudes', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
  })
}

/** Vista previa del PDF (no persiste nada) para revisar antes de confirmar el envío. */
export function usePreviewSolicitudPublica() {
  return useMutation({
    mutationFn: async (values: SolicitudFormValues) => {
      const { data } = await api.post('/public/solicitudes/preview', values, {
        responseType: 'blob',
      })
      return URL.createObjectURL(data as Blob)
    },
  })
}
