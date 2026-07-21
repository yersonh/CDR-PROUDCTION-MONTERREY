import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'

export interface ConsultaSolicitudResult {
  referencia: string
  nombre: string
  tipo_certificado: string
  creado_at: string
  codigo: string
  label: string
  descripcion: string
  radicado_vur: string | null
  radicado_cdr: string | null
}

interface ConsultaSolicitudError {
  encontrada: false
  message: string
}

export function useConsultarSolicitud(referencia: string | null) {
  return useQuery({
    queryKey: ['solicitud-publica', referencia],
    enabled: !!referencia,
    retry: false,
    queryFn: async () => {
      try {
        const { data } = await api.get<{ data: ConsultaSolicitudResult }>(`/public/solicitudes/${referencia}`)
        return { encontrada: true as const, ...data.data }
      } catch {
        return { encontrada: false, message: 'No se encontró una solicitud con esa referencia.' } as ConsultaSolicitudError
      }
    },
  })
}
