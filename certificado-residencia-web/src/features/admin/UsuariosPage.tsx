import { useState } from 'react'
import { Ban, Pencil, PlusCircle, Power, Search, UserCog } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Field } from '@/components/ui/field'
import { Modal } from '@/components/ui/modal'
import { RowActionButton } from '@/components/ui/row-action-button'
import { getApiErrorMessage } from '@/lib/api'
import type { User } from '@/types/auth'
import { useUsuarios, useGuardarUsuario, useToggleUsuario, useRoles } from './api'

const ROL_LABEL: Record<string, string> = {
  super_admin: 'Super Administrador', alcalde: 'Alcalde', secretaria: 'Secretaría',
  funcionario_sisben: 'Funcionario SISBEN', presidente_jac: 'Presidente JAC',
}

// Alcalde/Secretaría/Super Admin son cargos del Despacho del Alcalde; SISBEN
// y JAC son funcionarios externos que no pertenecen a ninguna dependencia
// (ver UsuarioController::resolverDependencia, misma regla en el backend).
const ROLES_DESPACHO_ALCALDE = ['super_admin', 'alcalde', 'secretaria']
const ROLES_SIN_DEPENDENCIA = ['funcionario_sisben', 'presidente_jac']

function dependenciaLabel(u: User): string {
  if (u.roles.some((r) => ROLES_SIN_DEPENDENCIA.includes(r))) return 'No aplica'
  return u.dependencia?.nombre ?? '—'
}

export function UsuariosPage() {
  const [buscar, setBuscar] = useState('')
  const { data, isLoading } = useUsuarios(buscar)
  const toggle = useToggleUsuario()
  const [editando, setEditando] = useState<User | null | undefined>(undefined) // undefined=cerrado, null=nuevo

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold text-white"><UserCog className="h-6 w-6 text-gold-light" /> Usuarios</h1>
          <p className="text-white/70">Gestión de usuarios, roles y dependencias.</p>
        </div>
        <Button variant="primary" onClick={() => setEditando(null)}><PlusCircle className="h-4 w-4" /> Nuevo usuario</Button>
      </div>

      <div className="mb-4 max-w-md">
        <div className="relative">
          <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
          <Input className="pl-9" placeholder="Buscar por nombre, correo o documento" value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        </div>
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
                <tr>
                  <th className="px-5 py-3 font-semibold">Nombre</th>
                  <th className="px-5 py-3 font-semibold">Correo</th>
                  <th className="px-5 py-3 font-semibold">Rol</th>
                  <th className="px-5 py-3 font-semibold">Dependencia</th>
                  <th className="px-5 py-3 font-semibold">Estado</th>
                  <th className="px-5 py-3 font-semibold"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && <tr><td colSpan={6} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
                {data?.data.map((u) => (
                  <tr key={u.id} className="hover:bg-primary-50/30">
                    <td className="px-5 py-3 font-medium text-institutional-text">{u.name}</td>
                    <td className="px-5 py-3 text-institutional-muted">{u.email}</td>
                    <td className="px-5 py-3">
                      {u.roles.length
                        ? u.roles.map((r) => ROL_LABEL[r] ?? r).join(', ')
                        : <span className="text-institutional-muted">Cuenta de sistema</span>}
                    </td>
                    <td className="px-5 py-3 text-institutional-muted">{dependenciaLabel(u)}</td>
                    <td className="px-5 py-3">
                      <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${u.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                        {u.activo ? 'Activo' : 'Inactivo'}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-right">
                      {u.roles.length > 0 && (
                        <div className="flex justify-end gap-1">
                          <RowActionButton icon={Pencil} label="Editar" onClick={() => setEditando(u)} />
                          <RowActionButton
                            icon={u.activo ? Ban : Power}
                            label={u.activo ? 'Desactivar' : 'Activar'}
                            variant={u.activo ? 'danger' : 'success'}
                            onClick={() => toggle.mutate(u.id)}
                          />
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {editando !== undefined && <UsuarioModal usuario={editando} onClose={() => setEditando(undefined)} />}
    </div>
  )
}

function UsuarioModal({ usuario, onClose }: { usuario: User | null; onClose: () => void }) {
  const esNuevo = usuario === null
  const { data: roles } = useRoles()
  const guardar = useGuardarUsuario()
  const [f, setF] = useState({
    name: usuario?.name ?? '', email: usuario?.email ?? '', celular: usuario?.celular ?? '',
    rol: usuario?.roles[0] ?? '',
  })
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setF((p) => ({ ...p, [k]: e.target.value }))

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    const payload: Record<string, unknown> = {
      name: f.name, email: f.email, celular: f.celular || null, rol: f.rol,
    }
    guardar.mutate({ id: usuario?.id, payload }, { onSuccess: onClose })
  }

  return (
    <Modal open onClose={onClose} title={esNuevo ? 'Nuevo usuario' : 'Editar usuario'}>
      {guardar.isError && (
        <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">
          {getApiErrorMessage(guardar.error, 'No fue posible guardar el usuario.')}
        </div>
      )}
      <form onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Nombre" htmlFor="u-name" required><Input id="u-name" value={f.name} onChange={set('name')} required /></Field>
          <Field label="Correo" htmlFor="u-email" required><Input id="u-email" type="email" value={f.email} onChange={set('email')} required /></Field>
          <Field label="Rol" htmlFor="u-rol" required>
            <Select id="u-rol" value={f.rol} onChange={set('rol')} required>
              <option value="">Seleccione…</option>
              {roles?.map((r) => <option key={r.name} value={r.name}>{ROL_LABEL[r.name] ?? r.name}</option>)}
            </Select>
          </Field>
          <Field label="Celular" htmlFor="u-cel"><Input id="u-cel" value={f.celular} onChange={set('celular')} /></Field>
        </div>
        {f.rol && (
          <p className="text-xs text-institutional-muted">
            {ROLES_DESPACHO_ALCALDE.includes(f.rol) && 'Dependencia: Despacho del Alcalde (fija para este rol).'}
            {ROLES_SIN_DEPENDENCIA.includes(f.rol) && 'Este rol es un funcionario externo, sin dependencia.'}
          </p>
        )}
        {esNuevo && (
          <p className="text-xs text-institutional-muted">
            Se generará una contraseña temporal y se enviará por correo al usuario, con 24 horas para cambiarla.
          </p>
        )}
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="primary" loading={guardar.isPending}>Guardar</Button>
        </div>
      </form>
    </Modal>
  )
}
