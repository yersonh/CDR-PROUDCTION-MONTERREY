import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Paginated, Solicitud } from './types'

export interface SolicitudFilters {
  estado?: string
  buscar?: string
  medio_acreditacion?: string
  page?: number
}

export function useSolicitudes(filters: SolicitudFilters = {}) {
  return useQuery({
    queryKey: ['solicitudes', filters],
    queryFn: async () => {
      const { data } = await api.get<Paginated<Solicitud>>('/solicitudes', {
        params: {
          estado: filters.estado || undefined,
          buscar: filters.buscar || undefined,
          medio_acreditacion: filters.medio_acreditacion || undefined,
          page: filters.page,
        },
      })
      return data
    },
  })
}

export function useSolicitud(id: number | string | undefined) {
  return useQuery({
    queryKey: ['solicitud', id],
    enabled: !!id,
    queryFn: async () => {
      const { data } = await api.get<{ data: Solicitud }>(`/solicitudes/${id}`)
      return data.data
    },
  })
}

export interface CreateSolicitudResult {
  data: Solicitud
  message: string
}

export function useCreateSolicitud() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await api.post<CreateSolicitudResult>('/solicitudes', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['solicitudes'] })
    },
  })
}

/** Registrar validación/carga de soporte (electoral, SISBEN, JAC, especial). */
export function useRegistrarValidacion(solicitudId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await api.post(`/solicitudes/${solicitudId}/validaciones`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['solicitud', String(solicitudId)] })
      queryClient.invalidateQueries({ queryKey: ['solicitudes'] })
    },
  })
}

export interface FirmarResult {
  message: string
  firmadas: string[]
  errores: Record<string, string>
}

/** Firma (individual o masiva) de solicitudes preaprobadas. */
export function useFirmar() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (solicitud_ids: number[]) => {
      const { data } = await api.post<FirmarResult>('/certificados/firmar', { solicitud_ids })
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['solicitudes'] })
      queryClient.invalidateQueries({ queryKey: ['solicitud'] })
    },
  })
}

/** Descarga autenticada del PDF del certificado. */
export async function descargarCertificadoPdf(solicitudId: number, consecutivo: string) {
  const res = await api.get(`/solicitudes/${solicitudId}/certificado/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(res.data as Blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `Certificado_${consecutivo}.pdf`
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(url)
}

/** Abre un documento del expediente en una pestaña nueva (autenticado, vía blob). */
export async function verDocumentoExpediente(solicitudId: number, documentoId: number) {
  const res = await api.get(`/solicitudes/${solicitudId}/documentos/${documentoId}/descargar`, {
    responseType: 'blob',
  })
  const url = URL.createObjectURL(res.data as Blob)
  window.open(url, '_blank', 'noopener')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

/** Subsanación del ciudadano: re-cargar soporte / actualizar justificación. */
export function useSubsanar(solicitudId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await api.post(`/solicitudes/${solicitudId}/subsanar`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['solicitud', String(solicitudId)] })
      queryClient.invalidateQueries({ queryKey: ['solicitudes'] })
    },
  })
}

export interface PrevalidacionPayload {
  resultado: 'cumple' | 'subsanar' | 'rechaza'
  observacion?: string
}

/** Emitir concepto de prevalidación. */
export function usePrevalidar(solicitudId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (payload: PrevalidacionPayload) => {
      const { data } = await api.post<{ data: Solicitud; message: string }>(
        `/solicitudes/${solicitudId}/prevalidacion`,
        payload,
      )
      return data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['solicitud', String(solicitudId)] })
      queryClient.invalidateQueries({ queryKey: ['solicitudes'] })
    },
  })
}
