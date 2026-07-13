import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { LogOut, ShieldCheck, UserCircle } from 'lucide-react'
import { useAuth } from '@/features/auth/useAuth'
import { useFotoPerfil } from '@/features/perfil/useFotoPerfil'

const ROL_LABEL: Record<string, string> = {
  super_admin: 'Administrador',
  alcalde: 'Alcalde',
  secretaria: 'Secretaría',
  recepcionista: 'Recepción',
  operador: 'Operador',
  funcionario_sisben: 'Funcionario SISBEN',
  presidente_jac: 'Presidente JAC',
  ciudadano: 'Ciudadano',
}

function iniciales(nombre?: string) {
  if (!nombre) return ''
  return nombre.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')
}

/** Avatar + menú desplegable del usuario en el header (foto, datos, cerrar sesión). */
export function UserMenu({ onLogoutClick }: { onLogoutClick: () => void }) {
  const { user } = useAuth()
  const fotoUrl = useFotoPerfil(user?.tiene_foto)
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return
    const onClickOutside = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClickOutside)
    return () => document.removeEventListener('mousedown', onClickOutside)
  }, [open])

  const rolLabel = user?.roles[0] ? (ROL_LABEL[user.roles[0]] ?? user.roles[0].replaceAll('_', ' ')) : ''

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-white/10"
      >
        <Avatar fotoUrl={fotoUrl} nombre={user?.name} size={36} />
        <div className="hidden text-right leading-tight sm:block">
          <p className="text-sm font-medium">{user?.name}</p>
          <p className="text-xs capitalize text-white/60">{rolLabel}</p>
        </div>
      </button>

      {open && (
        <div className="absolute right-0 top-full z-40 mt-2 w-72 rounded-xl border border-institutional-border bg-white p-4 text-institutional-text shadow-lg">
          <div className="flex items-center gap-3">
            <Avatar fotoUrl={fotoUrl} nombre={user?.name} size={48} />
            <div className="min-w-0">
              <p className="truncate font-semibold text-institutional-text">{user?.name}</p>
              <p className="truncate text-xs text-institutional-muted">{user?.email}</p>
            </div>
          </div>
          {rolLabel && (
            <span className="mt-3 inline-flex items-center gap-1 rounded-full bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary">
              <ShieldCheck className="h-3.5 w-3.5" /> {rolLabel}
            </span>
          )}

          <div className="my-3 border-t border-institutional-border" />

          <Link
            to="/perfil"
            onClick={() => setOpen(false)}
            className="flex items-center gap-2 rounded-lg px-2 py-2 text-sm text-institutional-text transition-colors hover:bg-institutional-bg"
          >
            <UserCircle className="h-4 w-4" /> Mi perfil
          </Link>
          <button
            type="button"
            onClick={() => { setOpen(false); onLogoutClick() }}
            className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-danger transition-colors hover:bg-red-50"
          >
            <LogOut className="h-4 w-4" /> Cerrar sesión
          </button>
        </div>
      )}
    </div>
  )
}

function Avatar({ fotoUrl, nombre, size }: { fotoUrl: string | null; nombre?: string; size: number }) {
  return (
    <div
      className="flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gold-light font-semibold text-primary ring-2 ring-white/20"
      style={{ width: size, height: size, fontSize: size * 0.38 }}
    >
      {fotoUrl ? (
        <img src={fotoUrl} alt={nombre ?? 'Foto de perfil'} className="h-full w-full object-cover" />
      ) : (
        iniciales(nombre)
      )}
    </div>
  )
}
