import { useState } from 'react'
import { MapPin, PlusCircle } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Field } from '@/components/ui/field'
import { Modal } from '@/components/ui/modal'
import { getApiErrorMessage } from '@/lib/api'
import { useSectoresAdmin, useGuardarSector, type SectorAdmin } from './api'

export function SectoresPage() {
  const { data, isLoading } = useSectoresAdmin()
  const [editando, setEditando] = useState<SectorAdmin | null | undefined>(undefined)

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold text-white"><MapPin className="h-6 w-6 text-gold-light" /> Sectores</h1>
          <p className="text-white/70">Catálogo de barrios y veredas de Monterrey, Casanare.</p>
        </div>
        <Button variant="primary" onClick={() => setEditando(null)}><PlusCircle className="h-4 w-4" /> Nuevo sector</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
              <tr>
                <th className="px-5 py-3 font-semibold">Nombre</th>
                <th className="px-5 py-3 font-semibold">Tipo</th>
                <th className="px-5 py-3 font-semibold">Zona</th>
                <th className="px-5 py-3 font-semibold">Presidentes JAC</th>
                <th className="px-5 py-3 font-semibold">Estado</th>
                <th className="px-5 py-3 font-semibold"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-institutional-border">
              {isLoading && <tr><td colSpan={6} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
              {data?.map((s) => (
                <tr key={s.id} className="hover:bg-primary-50/30">
                  <td className="px-5 py-3 font-medium text-institutional-text">{s.nombre}</td>
                  <td className="px-5 py-3 capitalize text-institutional-muted">{s.tipo}</td>
                  <td className="px-5 py-3 capitalize text-institutional-muted">{s.zona}</td>
                  <td className="px-5 py-3 text-institutional-muted">{s.presidentes_jac_count}</td>
                  <td className="px-5 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${s.activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'}`}>
                      {s.activo ? 'Activo' : 'Inactivo'}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-right">
                    <button onClick={() => setEditando(s)} className="text-sm text-primary hover:underline">Editar</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>

      {editando !== undefined && <SectorModal sector={editando} onClose={() => setEditando(undefined)} />}
    </div>
  )
}

function SectorModal({ sector, onClose }: { sector: SectorAdmin | null; onClose: () => void }) {
  const guardar = useGuardarSector()
  const [f, setF] = useState({
    nombre: sector?.nombre ?? '', tipo: sector?.tipo ?? 'barrio', zona: sector?.zona ?? 'urbana',
    activo: sector?.activo ?? true,
  })

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    guardar.mutate({ id: sector?.id, payload: f }, { onSuccess: onClose })
  }

  return (
    <Modal open onClose={onClose} title={sector ? 'Editar sector' : 'Nuevo sector'}>
      {guardar.isError && (
        <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">
          {getApiErrorMessage(guardar.error, 'No fue posible guardar el sector.')}
        </div>
      )}
      <form onSubmit={submit} className="space-y-4">
        <Field label="Nombre" htmlFor="s-nom" required>
          <Input id="s-nom" value={f.nombre} onChange={(e) => setF((p) => ({ ...p, nombre: e.target.value }))} required placeholder="Barrio Centro" />
        </Field>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Tipo" htmlFor="s-tipo" required>
            <Select id="s-tipo" value={f.tipo} onChange={(e) => setF((p) => ({ ...p, tipo: e.target.value as 'barrio' | 'vereda' }))}>
              <option value="barrio">Barrio</option>
              <option value="vereda">Vereda</option>
            </Select>
          </Field>
          <Field label="Zona" htmlFor="s-zona" required>
            <Select id="s-zona" value={f.zona} onChange={(e) => setF((p) => ({ ...p, zona: e.target.value as 'urbana' | 'rural' }))}>
              <option value="urbana">Urbana</option>
              <option value="rural">Rural</option>
            </Select>
          </Field>
        </div>
        {sector && (
          <Field label="Estado" htmlFor="s-activo">
            <Select id="s-activo" value={f.activo ? '1' : '0'} onChange={(e) => setF((p) => ({ ...p, activo: e.target.value === '1' }))}>
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </Select>
          </Field>
        )}
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="primary" loading={guardar.isPending}>Guardar</Button>
        </div>
      </form>
    </Modal>
  )
}
