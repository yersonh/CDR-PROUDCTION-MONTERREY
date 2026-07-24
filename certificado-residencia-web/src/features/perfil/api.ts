import { api } from '@/lib/api'

/** Descarga el manual de usuario del rol del usuario autenticado y lo abre en una pestaña nueva. */
export async function descargarManual() {
  const res = await api.get('/perfil/manual', {
    responseType: 'blob',
    headers: { 'Cache-Control': 'no-cache' },
  })
  const url = URL.createObjectURL(res.data as Blob)
  window.open(url, '_blank', 'noopener')
  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}
