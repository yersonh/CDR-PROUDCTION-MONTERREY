import { useState } from 'react'
import { Building2, Pencil, PlusCircle, Trash2 } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Field } from '@/components/ui/field'
import { Modal } from '@/components/ui/modal'
import { RowActionButton } from '@/components/ui/row-action-button'
import { getApiErrorMessage } from '@/lib/api'
import { useDependencias, useGuardarDependencia, useEliminarDependencia, type DependenciaAdmin } from './api'

export function DependenciasPage() {
  const { data, isLoading } = useDependencias()
  const eliminar = useEliminarDependencia()
  const [editando, setEditando] = useState<DependenciaAdmin | null | undefined>(undefined)

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold text-white"><Building2 className="h-6 w-6 text-gold-light" /> Dependencias</h1>
          <p className="text-white/70">Catálogo organizacional de la Alcaldía.</p>
        </div>
        <Button variant="primary" onClick={() => setEditando(null)}><PlusCircle className="h-4 w-4" /> Nueva dependencia</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
              <tr>
                <th className="px-5 py-3 font-semibold">Nombre</th>
                <th className="px-5 py-3 font-semibold">Código</th>
                <th className="px-5 py-3 font-semibold">Usuarios</th>
                <th className="px-5 py-3 font-semibold">Estado</th>
                <th className="px-5 py-3 font-semibold"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-institutional-border">
              {isLoading && <tr><td colSpan={5} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
              {data?.map((d) => (
                <tr key={d.id} className="hover:bg-primary-50/30">
                  <td className="px-5 py-3 font-medium text-institutional-text">{d.nombre}</td>
                  <td className="px-5 py-3 text-institutional-muted">{d.codigo ?? '—'}</td>
                  <td className="px-5 py-3 text-institutional-muted">{d.usuarios_count}</td>
                  <td className="px-5 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${d.activa ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'}`}>
                      {d.activa ? 'Activa' : 'Inactiva'}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      <RowActionButton icon={Pencil} label="Editar" onClick={() => setEditando(d)} />
                      {d.usuarios_count === 0 && (
                        <RowActionButton icon={Trash2} label="Eliminar" variant="danger" onClick={() => eliminar.mutate(d.id)} />
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>

      {editando !== undefined && <DependenciaModal dependencia={editando} onClose={() => setEditando(undefined)} />}
    </div>
  )
}

function DependenciaModal({ dependencia, onClose }: { dependencia: DependenciaAdmin | null; onClose: () => void }) {
  const guardar = useGuardarDependencia()
  const [nombre, setNombre] = useState(dependencia?.nombre ?? '')
  const [codigo, setCodigo] = useState(dependencia?.codigo ?? '')

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    guardar.mutate({ id: dependencia?.id, payload: { nombre, codigo: codigo || null } }, { onSuccess: onClose })
  }

  return (
    <Modal open onClose={onClose} title={dependencia ? 'Editar dependencia' : 'Nueva dependencia'}>
      {guardar.isError && (
        <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">
          {getApiErrorMessage(guardar.error, 'No fue posible guardar.')}
        </div>
      )}
      <form onSubmit={submit} className="space-y-4">
        <Field label="Nombre" htmlFor="d-nom" required><Input id="d-nom" value={nombre} onChange={(e) => setNombre(e.target.value)} required /></Field>
        <Field label="Código" htmlFor="d-cod"><Input id="d-cod" value={codigo} onChange={(e) => setCodigo(e.target.value)} placeholder="Opcional" /></Field>
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="primary" loading={guardar.isPending}>Guardar</Button>
        </div>
      </form>
    </Modal>
  )
}
