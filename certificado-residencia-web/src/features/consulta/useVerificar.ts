import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'

export interface VerificacionResult {
  valido: boolean
  vigente?: boolean
  message?: string
  certificado?: {
    consecutivo: string
    codigo_verificacion: string
    estado: string
    tipo: string
    radicado: string
    ciudadano: string
    identificacion: string
    autoridad: string
    firmado_por: string | null
    fecha_expedicion: string
    vigencia_hasta: string
    hash_documento: string
  }
}

export function useVerificar(codigo: string | null) {
  return useQuery({
    queryKey: ['verificar', codigo],
    enabled: !!codigo,
    retry: false,
    queryFn: async () => {
      try {
        const { data } = await api.get<VerificacionResult>(`/public/verificar/${codigo}`)
        return data
      } catch (err) {
        return { valido: false, message: 'No se encontró un certificado con ese código.' } as VerificacionResult
      }
    },
  })
}
