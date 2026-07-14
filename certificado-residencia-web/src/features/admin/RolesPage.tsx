import { ShieldCheck, Users } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useRoles } from './api'

const ROL_LABEL: Record<string, string> = {
  super_admin: 'Super Administrador', alcalde: 'Alcalde', secretaria: 'Secretaría',
  funcionario_sisben: 'Funcionario SISBEN', presidente_jac: 'Presidente JAC',
}

const PERMISO_LABEL: Record<string, string> = {
  'solicitudes.crear': 'Crear solicitudes',
  'solicitudes.ver_propias': 'Ver solicitudes propias',
  'solicitudes.ver_todas': 'Ver todas las solicitudes',
  'solicitudes.direccionar': 'Direccionar solicitudes',
  'recibidos-vur.crear': 'Registrar recibidos de VUR',
  'recibidos-vur.ver': 'Ver recibidos de VUR',
  'soportes.subir': 'Subir soportes',
  'soportes.validar_electoral': 'Validar soporte electoral',
  'soportes.cargar_sisben': 'Cargar soporte SISBEN',
  'soportes.cargar_jac': 'Cargar soporte JAC',
  'validacion.prevalidar': 'Prevalidar solicitudes',
  'firma.ver_bandeja': 'Ver bandeja de firma',
  'firma.firmar': 'Firmar certificados',
  'certificados.ver': 'Ver certificados',
  'certificados.revocar': 'Revocar certificados',
  'expedientes.ver': 'Ver expedientes',
  'dashboard.ver': 'Ver dashboard',
  'auditoria.ver': 'Ver auditoría',
  'reportes.ver': 'Ver reportes',
  'admin.usuarios': 'Administrar usuarios',
  'admin.roles': 'Administrar roles',
  'admin.dependencias': 'Administrar dependencias',
}

export function RolesPage() {
  const { data, isLoading } = useRoles()

  return (
    <div className="animate-fade-up">
      <div className="mb-6">
        <h1 className="flex items-center gap-2 text-2xl font-bold text-white"><ShieldCheck className="h-6 w-6 text-gold-light" /> Roles y permisos</h1>
        <p className="text-white/70">Esquema de permisos por rol del sistema.</p>
      </div>

      {isLoading && <p className="text-white/70">Cargando…</p>}

      <div className="grid gap-5 md:grid-cols-2">
        {data?.map((r) => (
          <Card key={r.id}>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>{ROL_LABEL[r.name] ?? r.name}</CardTitle>
              <span className="flex items-center gap-1 text-xs text-institutional-muted"><Users className="h-3.5 w-3.5" /> {r.usuarios}</span>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-1.5">
                {r.permisos.map((p) => (
                  <span key={p} title={p} className="rounded-md bg-institutional-bg px-2 py-0.5 text-xs text-institutional-text">{PERMISO_LABEL[p] ?? p}</span>
                ))}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
