import { useMutation, useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'

export interface SubsanacionPublicaInfo {
  radicado: string
  nombre_completo: string
  medio_acreditacion: 'electoral' | 'sisben' | 'jac'
  estado: string
  observacion: string | null
}

/** Query string firmada (expires + signature) que llega en el enlace del correo. */
function firmaQuery(search: string): string {
  const params = new URLSearchParams(search)
  const out = new URLSearchParams()
  if (params.get('expires')) out.set('expires', params.get('expires')!)
  if (params.get('signature')) out.set('signature', params.get('signature')!)
  return out.toString()
}

export function useSubsanacionPublicaInfo(solicitudId: string | undefined, search: string) {
  const firma = firmaQuery(search)

  return useQuery({
    queryKey: ['subsanacion-publica', solicitudId, firma],
    enabled: !!solicitudId && !!firma,
    retry: false,
    queryFn: async () => {
      const { data } = await api.get<{ data: SubsanacionPublicaInfo }>(
        `/public/subsanacion/${solicitudId}?${firma}`,
      )
      return data.data
    },
  })
}

export function useEnviarSubsanacionPublica(solicitudId: string | undefined, search: string) {
  const firma = firmaQuery(search)

  return useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await api.post(`/public/subsanacion/${solicitudId}?${firma}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    },
  })
}
