import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import {
  Building2,
  FileText,
  History,
  LayoutDashboard,
  LogOut,
  Menu,
  PlusCircle,
  ShieldCheck,
  Stamp,
  UserCircle,
  UserCog,
  X,
} from 'lucide-react'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import { useAuth } from '@/features/auth/useAuth'
import { ConfirmDialog } from '@/components/ui/confirm-dialog'
import { NexGovIAInfoModal } from '@/components/nexgovia-info-modal'
import escudo from '@/assets/logo-alcaldia.png'

interface NavItem {
  to: string
  label: string
  icon: React.ElementType
  permiso?: string
}

const NAV: NavItem[] = [
  { to: '/dashboard', label: 'Inicio', icon: LayoutDashboard },
  { to: '/solicitudes', label: 'Solicitudes', icon: FileText },
  { to: '/solicitudes/nueva', label: 'Nueva solicitud', icon: PlusCircle, permiso: 'solicitudes.crear' },
  { to: '/firma', label: 'Bandeja de firma', icon: Stamp, permiso: 'firma.ver_bandeja' },
  { to: '/admin/usuarios', label: 'Usuarios', icon: UserCog, permiso: 'admin.usuarios' },
  { to: '/admin/dependencias', label: 'Dependencias', icon: Building2, permiso: 'admin.dependencias' },
  { to: '/admin/roles', label: 'Roles', icon: ShieldCheck, permiso: 'admin.roles' },
  { to: '/auditoria', label: 'Auditoría', icon: History, permiso: 'auditoria.ver' },
  { to: '/perfil', label: 'Mi perfil', icon: UserCircle },
]

export function AppLayout() {
  const { user, clearSession, hasPermission } = useAuth()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const [confirmLogoutOpen, setConfirmLogoutOpen] = useState(false)
  const [loggingOut, setLoggingOut] = useState(false)
  const [showNexGovIA, setShowNexGovIA] = useState(false)

  const items = NAV.filter((i) => !i.permiso || hasPermission(i.permiso))

  const handleLogout = async () => {
    setLoggingOut(true)
    try {
      await api.post('/auth/logout')
    } finally {
      clearSession()
      navigate('/login', { replace: true })
    }
  }

  return (
    <div className="flex min-h-screen flex-col bg-institutional-bg">
      {/* Header institucional */}
      <header className="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 bg-primary px-4 text-white shadow-md sm:px-6">
        <div className="flex items-center gap-3">
          <button
            className="rounded-md p-1.5 hover:bg-white/10 lg:hidden"
            onClick={() => setOpen((v) => !v)}
            aria-label="Alternar menú"
          >
            {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
          <div className="group h-10 w-10 rounded-full bg-gradient-to-br from-gold-light via-gold to-gold-light p-[2px] shadow-sm transition-all duration-300 ease-out hover:scale-110 hover:shadow-[0_0_16px_3px_rgba(200,168,0,0.5)]">
            <div className="flex h-full w-full items-center justify-center rounded-full bg-white p-1">
              <img
                src={escudo}
                alt="Escudo Alcaldía de Monterrey"
                className="h-full w-full rounded-full object-contain transition-transform duration-300 group-hover:scale-110"
              />
            </div>
          </div>
          <div className="leading-tight">
            <p className="text-sm font-semibold">Alcaldía de Monterrey · Casanare</p>
            <p className="hidden text-xs text-white/70 sm:block">Certificado de Residencia Digital</p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <div className="hidden text-right sm:block">
            <p className="text-sm font-medium leading-tight">{user?.name}</p>
            <p className="text-xs capitalize text-white/60">{user?.roles.join(', ').replaceAll('_', ' ')}</p>
          </div>
          <button
            onClick={() => setConfirmLogoutOpen(true)}
            className="flex items-center gap-1.5 rounded-lg border border-white/25 bg-white/10 px-3 py-1.5 text-sm hover:bg-white/20"
          >
            <LogOut className="h-4 w-4" /> Salir
          </button>
        </div>
      </header>

      <div className="flex flex-1">
        {/* Sidebar */}
        <aside
          className={cn(
            'fixed inset-y-0 left-0 top-16 z-20 w-64 transform border-r border-institutional-border bg-white p-4 transition-transform lg:static lg:top-0 lg:translate-x-0',
            open ? 'translate-x-0' : '-translate-x-full',
          )}
        >
          <nav className="space-y-1">
            {items.map(({ to, label, icon: Icon }) => (
              <NavLink
                key={to}
                to={to}
                end={to === '/solicitudes'}
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                    isActive
                      ? 'bg-primary text-white shadow-sm'
                      : 'text-institutional-text hover:bg-institutional-bg',
                  )
                }
              >
                <Icon className="h-4 w-4" />
                {label}
              </NavLink>
            ))}
          </nav>
        </aside>

        {/* Overlay móvil */}
        {open && (
          <div
            className="fixed inset-0 top-16 z-10 bg-black/30 lg:hidden"
            onClick={() => setOpen(false)}
            aria-hidden
          />
        )}

        {/* Contenido */}
        <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
          <Outlet />
        </main>
      </div>

      <footer className="bg-primary py-3 text-center text-xs text-white/70">
        <button
          type="button"
          onClick={() => setShowNexGovIA(true)}
          className="underline-offset-2 transition hover:text-gold-light hover:underline"
        >
          Desarrollado por NexGovIA · Sovereign Data and AI
        </button>
      </footer>

      <NexGovIAInfoModal open={showNexGovIA} onClose={() => setShowNexGovIA(false)} />

      <ConfirmDialog
        open={confirmLogoutOpen}
        title="Cerrar sesión"
        description="¿Está seguro que desea cerrar la sesión actual?"
        confirmLabel="Sí, salir"
        cancelLabel="Cancelar"
        variant="danger"
        loading={loggingOut}
        onConfirm={handleLogout}
        onCancel={() => setConfirmLogoutOpen(false)}
      />
    </div>
  )
}
