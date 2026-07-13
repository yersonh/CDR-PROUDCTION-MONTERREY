import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { Loader2 } from 'lucide-react'
import { useAuth } from './useAuth'

const RUTA_CAMBIO_OBLIGATORIO = '/cambiar-password-temporal'

export function ProtectedRoute() {
  const { user, isAuthenticated, isLoading } = useAuth()
  const location = useLocation()

  if (isLoading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-primary">
        <Loader2 className="h-8 w-8 animate-spin text-white" aria-label="Cargando" />
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  // Contraseña temporal pendiente de cambio: bloquea cualquier otra pantalla
  // hasta que la reemplace (ver AuthController::login / must_change_password).
  if (user?.must_change_password && location.pathname !== RUTA_CAMBIO_OBLIGATORIO) {
    return <Navigate to={RUTA_CAMBIO_OBLIGATORIO} replace />
  }

  return <Outlet />
}
