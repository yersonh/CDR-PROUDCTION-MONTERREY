import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Bell, CheckCheck } from 'lucide-react'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import { useToast } from '@/components/ui/toast'
import {
  useMarcarNotificacionLeida,
  useMarcarTodasLeidas,
  useNotificaciones,
  useNotificacionesNoLeidas,
} from '@/features/notificaciones/api'
import type { Notificacion } from '@/features/notificaciones/types'

/** Campanita de notificaciones del header: mismo patrón que VUR (polling del contador, lista bajo demanda). */
export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const navigate = useNavigate()
  const { showToast } = useToast()

  const { data: noLeidas = 0 } = useNotificacionesNoLeidas()
  const { data: notificaciones, isLoading } = useNotificaciones(open)
  const marcarLeida = useMarcarNotificacionLeida()
  const marcarTodas = useMarcarTodasLeidas()

  useEffect(() => {
    if (!open) return
    const onClickOutside = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [open])

  // Popup emergente cuando llega una notificación nueva (no basta con que
  // suba el contador — el usuario debe enterarse aunque no abra la
  // campanita). Se compara contra el conteo anterior para no disparar nada
  // en el primer render, y contra los ids ya mostrados para no repetir un
  // toast si el usuario abre/cierra el panel entre polls.
  const conteoAnterior = useRef<number | null>(null)
  const idsNotificados = useRef<Set<number>>(new Set())

  useEffect(() => {
    if (conteoAnterior.current === null) {
      conteoAnterior.current = noLeidas
      return
    }
    if (noLeidas <= conteoAnterior.current) {
      conteoAnterior.current = noLeidas
      return
    }
    conteoAnterior.current = noLeidas

    api.get<{ data: Notificacion[] }>('/notificaciones').then(({ data }) => {
      data.data
        .filter((n) => !n.leida && !idsNotificados.current.has(n.id))
        .forEach((n) => {
          idsNotificados.current.add(n.id)
          showToast({
            title: 'Nueva notificación',
            description: n.mensaje,
            onClick: () => navigate(n.solicitud_id ? `/solicitudes/${n.solicitud_id}` : '/solicitudes'),
          })
        })
    })
  }, [noLeidas, navigate, showToast])

  const onClickNotificacion = (n: Notificacion) => {
    if (!n.leida) marcarLeida.mutate(n.id)
    setOpen(false)
  }

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="relative rounded-md p-2 transition-colors hover:bg-white/10"
        aria-label="Notificaciones"
      >
        <Bell className="h-5 w-5" />
        {noLeidas > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold leading-none text-white">
            {noLeidas > 9 ? '9+' : noLeidas}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 top-full z-40 mt-2 w-80 rounded-xl border border-institutional-border bg-white p-3 text-institutional-text shadow-lg">
          <div className="mb-2 flex items-center justify-between px-1">
            <p className="text-sm font-semibold">Notificaciones</p>
            {noLeidas > 0 && (
              <button
                type="button"
                onClick={() => marcarTodas.mutate()}
                className="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
              >
                <CheckCheck className="h-3.5 w-3.5" /> Marcar todas leídas
              </button>
            )}
          </div>

          <div className="max-h-96 space-y-1 overflow-y-auto">
            {isLoading && <p className="py-6 text-center text-xs text-institutional-muted">Cargando…</p>}
            {!isLoading && notificaciones?.length === 0 && (
              <p className="py-6 text-center text-xs text-institutional-muted">No tiene notificaciones.</p>
            )}
            {notificaciones?.map((n) => (
              <Link
                key={n.id}
                to={n.solicitud_id ? `/solicitudes/${n.solicitud_id}` : '/solicitudes'}
                onClick={() => onClickNotificacion(n)}
                className={cn(
                  'block rounded-lg px-3 py-2 text-sm transition-colors hover:bg-primary-50/40',
                  !n.leida && 'bg-primary-50/60 font-medium',
                )}
              >
                <p className="text-institutional-text">{n.mensaje}</p>
                <p className="mt-0.5 text-xs text-institutional-muted">
                  {new Date(n.created_at).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' })}
                </p>
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
