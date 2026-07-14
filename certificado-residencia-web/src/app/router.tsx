import { createBrowserRouter, Navigate } from 'react-router-dom'
import { LoginPage } from '@/features/auth/LoginPage'
import { ForgotPasswordPage } from '@/features/auth/ForgotPasswordPage'
import { ResetPasswordPage } from '@/features/auth/ResetPasswordPage'
import { ProtectedRoute } from '@/features/auth/ProtectedRoute'
import { CambiarPasswordTemporalPage } from '@/features/auth/CambiarPasswordTemporalPage'
import { AppLayout } from '@/components/layout/AppLayout'
import { DashboardPage } from '@/features/dashboard/DashboardPage'
import { MisSolicitudesPage } from '@/features/solicitudes/MisSolicitudesPage'
import { RecibidosVurPage } from '@/features/recibidos-vur/RecibidosVurPage'
import { SolicitudDetallePage } from '@/features/solicitudes/SolicitudDetallePage'
import { FirmaPage } from '@/features/firma/FirmaPage'
import { VerificarPage } from '@/features/consulta/VerificarPage'
import { SolicitudPublicaPage } from '@/features/solicitud-publica/SolicitudPublicaPage'
import { SubsanacionPublicaPage } from '@/features/subsanacion-publica/SubsanacionPublicaPage'
import { AuditoriaPage } from '@/features/auditoria/AuditoriaPage'
import { ReportesPage } from '@/features/reportes/ReportesPage'
import { PerfilPage } from '@/features/perfil/PerfilPage'
import { UsuariosPage } from '@/features/admin/UsuariosPage'
import { DependenciasPage } from '@/features/admin/DependenciasPage'
import { RolesPage } from '@/features/admin/RolesPage'
import { SectoresPage } from '@/features/admin/SectoresPage'
import { PresidentesJacPage } from '@/features/admin/PresidentesJacPage'

export const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  { path: '/recuperar', element: <ForgotPasswordPage /> },
  { path: '/restablecer', element: <ResetPasswordPage /> },
  { path: '/verificar', element: <VerificarPage /> },
  { path: '/solicitud-publica', element: <SolicitudPublicaPage /> },
  { path: '/corregir/:id', element: <SubsanacionPublicaPage /> },
  {
    element: <ProtectedRoute />,
    children: [
      { path: '/cambiar-password-temporal', element: <CambiarPasswordTemporalPage /> },
      {
        element: <AppLayout />,
        children: [
          { path: '/dashboard', element: <DashboardPage /> },
          { path: '/solicitudes', element: <MisSolicitudesPage /> },
          { path: '/recibidos-vur', element: <RecibidosVurPage /> },
          { path: '/solicitudes/:id', element: <SolicitudDetallePage /> },
          { path: '/firma', element: <FirmaPage /> },
          { path: '/auditoria', element: <AuditoriaPage /> },
          { path: '/reportes', element: <ReportesPage /> },
          { path: '/perfil', element: <PerfilPage /> },
          { path: '/admin/usuarios', element: <UsuariosPage /> },
          { path: '/admin/dependencias', element: <DependenciasPage /> },
          { path: '/admin/roles', element: <RolesPage /> },
          { path: '/admin/sectores', element: <SectoresPage /> },
          { path: '/admin/presidentes-jac', element: <PresidentesJacPage /> },
        ],
      },
    ],
  },
  { path: '/', element: <Navigate to="/dashboard" replace /> },
  { path: '*', element: <Navigate to="/dashboard" replace /> },
])
