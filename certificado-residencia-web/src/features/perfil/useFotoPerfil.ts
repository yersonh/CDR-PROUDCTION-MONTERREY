import { useEffect, useRef, useState } from 'react'
import { api } from '@/lib/api'

/** Trae la foto de perfil del usuario autenticado como object URL (endpoint privado, no una URL pública). */
export function useFotoPerfil(tieneFoto: boolean | undefined) {
  const [url, setUrl] = useState<string | null>(null)
  const objectUrlRef = useRef<string | null>(null)

  useEffect(() => {
    let active = true

    if (!tieneFoto) {
      if (objectUrlRef.current) {
        URL.revokeObjectURL(objectUrlRef.current)
        objectUrlRef.current = null
      }
      setUrl(null)
      return
    }

    api.get('/perfil/foto', { responseType: 'blob' }).then((res) => {
      if (!active) return
      if (objectUrlRef.current) URL.revokeObjectURL(objectUrlRef.current)
      const nextUrl = URL.createObjectURL(res.data as Blob)
      objectUrlRef.current = nextUrl
      setUrl(nextUrl)
    }).catch(() => {})

    return () => {
      active = false
    }
  }, [tieneFoto])

  useEffect(() => () => {
    if (objectUrlRef.current) URL.revokeObjectURL(objectUrlRef.current)
  }, [])

  return url
}
