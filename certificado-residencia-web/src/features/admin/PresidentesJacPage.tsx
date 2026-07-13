import { useState } from 'react'
import { PlusCircle, RefreshCw, UserCheck } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select } from '@/components/ui/select'
import { Field } from '@/components/ui/field'
import { Modal } from '@/components/ui/modal'
import { RowActionButton } from '@/components/ui/row-action-button'
import { getApiErrorMessage } from '@/lib/api'
import {
  useSectoresAdmin, usePresidentesJac, useCrearPresidenteJac, useReemplazarPresidenteJac,
  type PresidenteJac,
} from './api'

export function PresidentesJacPage() {
  const { data: sectores } = useSectoresAdmin()
  const { data, isLoading } = usePresidentesJac()
  const [nuevoOpen, setNuevoOpen] = useState(false)
  const [reemplazando, setReemplazando] = useState<PresidenteJac | null>(null)

  const activos = data?.data.filter((p) => p.estado === 'activo') ?? []
  const sectoresConPresidente = new Set(activos.map((p) => p.sector.id))
  const sectoresDisponibles = sectores?.filter((s) => s.activo && !sectoresConPresidente.has(s.id)) ?? []

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold text-white"><UserCheck className="h-6 w-6 text-gold-light" /> Presidentes JAC</h1>
          <p className="text-white/70">Un presidente por sector.</p>
        </div>
        <Button variant="primary" onClick={() => setNuevoOpen(true)} disabled={sectoresDisponibles.length === 0}>
          <PlusCircle className="h-4 w-4" /> Nuevo presidente
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
              <tr>
                <th className="px-5 py-3 font-semibold">Sector</th>
                <th className="px-5 py-3 font-semibold">Nombre</th>
                <th className="px-5 py-3 font-semibold">Documento</th>
                <th className="px-5 py-3 font-semibold">Correo (login)</th>
                <th className="px-5 py-3 font-semibold">Periodo</th>
                <th className="px-5 py-3 font-semibold">Estado</th>
                <th className="px-5 py-3 font-semibold"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-institutional-border">
              {isLoading && <tr><td colSpan={7} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>}
              {data?.data.map((p) => (
                <tr key={p.id} className="hover:bg-primary-50/30">
                  <td className="px-5 py-3 font-medium text-institutional-text">{p.sector.nombre}</td>
                  <td className="px-5 py-3 text-institutional-text">{p.nombre_completo}</td>
                  <td className="px-5 py-3 text-institutional-muted">{p.tipo_documento} {p.numero_identificacion}</td>
                  <td className="px-5 py-3 text-institutional-muted">{p.user?.email ?? '—'}</td>
                  <td className="px-5 py-3 text-institutional-muted">
                    {p.fecha_inicio_periodo} {p.fecha_fin_periodo ? `— ${p.fecha_fin_periodo}` : ''}
                  </td>
                  <td className="px-5 py-3">
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${p.estado === 'activo' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'}`}>
                      {p.estado === 'activo' ? 'Activo' : 'Reemplazado'}
                    </span>
                  </td>
                  <td className="px-5 py-3 text-right">
                    {p.estado === 'activo' && (
                      <RowActionButton icon={RefreshCw} label="Reemplazar" onClick={() => setReemplazando(p)} />
                    )}
                  </td>
                </tr>
              ))}
              {!isLoading && data?.data.length === 0 && (
                <tr><td colSpan={7} className="px-5 py-10 text-center text-institutional-muted">Sin presidentes registrados.</td></tr>
              )}
            </tbody>
          </table>
        </CardContent>
      </Card>

      {nuevoOpen && (
        <PresidenteJacModal
          modo="crear"
          sectores={sectoresDisponibles}
          onClose={() => setNuevoOpen(false)}
        />
      )}
      {reemplazando && (
        <PresidenteJacModal
          modo="reemplazar"
          actual={reemplazando}
          onClose={() => setReemplazando(null)}
        />
      )}
    </div>
  )
}

function PresidenteJacModal({
  modo, sectores, actual, onClose,
}: {
  modo: 'crear' | 'reemplazar'
  sectores?: { id: number; nombre: string }[]
  actual?: PresidenteJac
  onClose: () => void
}) {
  const crear = useCrearPresidenteJac()
  const reemplazar = useReemplazarPresidenteJac()
  const mutando = modo === 'crear' ? crear : reemplazar

  const [f, setF] = useState({
    sector_id: sectores?.[0]?.id ? String(sectores[0].id) : '',
    nombre_completo: '', tipo_documento: 'CC', numero_identificacion: '',
    direccion: '', celular: '', correo: '',
    fecha_inicio_periodo: new Date().toISOString().slice(0, 10), fecha_fin_periodo: '',
  })
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setF((p) => ({ ...p, [k]: e.target.value }))

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    const payload = {
      ...f,
      fecha_fin_periodo: f.fecha_fin_periodo || undefined,
    }
    if (modo === 'crear') {
      crear.mutate({ ...payload, sector_id: Number(f.sector_id) }, { onSuccess: onClose })
    } else if (actual) {
      reemplazar.mutate({ id: actual.id, payload }, { onSuccess: onClose })
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={modo === 'crear' ? 'Nuevo presidente JAC' : `Reemplazar presidente — ${actual?.sector.nombre}`}
    >
      {mutando.isError && (
        <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">
          {getApiErrorMessage(mutando.error, 'No fue posible guardar.')}
        </div>
      )}
      {modo === 'reemplazar' && (
        <p className="mb-4 rounded-lg border border-warning/40 bg-amber-50 px-4 py-2.5 text-sm text-institutional-text">
          Esto cierra el periodo de <strong>{actual?.nombre_completo}</strong> y desactiva su acceso. El nuevo presidente
          quedará vinculado al mismo sector con su propio login.
        </p>
      )}
      <form onSubmit={submit} className="space-y-4">
        {modo === 'crear' && (
          <Field label="Sector" htmlFor="pj-sector" required>
            <Select id="pj-sector" value={f.sector_id} onChange={set('sector_id')} required>
              {sectores?.length === 0 && <option value="">Sin sectores disponibles</option>}
              {sectores?.map((s) => <option key={s.id} value={s.id}>{s.nombre}</option>)}
            </Select>
          </Field>
        )}
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Nombre completo" htmlFor="pj-nombre" required className="sm:col-span-2">
            <Input id="pj-nombre" value={f.nombre_completo} onChange={set('nombre_completo')} required />
          </Field>
          <Field label="Tipo de documento" htmlFor="pj-tipodoc" required>
            <Select id="pj-tipodoc" value={f.tipo_documento} onChange={set('tipo_documento')}>
              {['CC', 'TI', 'CE', 'PA'].map((t) => <option key={t} value={t}>{t}</option>)}
            </Select>
          </Field>
          <Field label="Número de documento" htmlFor="pj-numdoc" required>
            <Input id="pj-numdoc" value={f.numero_identificacion} onChange={set('numero_identificacion')} required />
          </Field>
          <Field label="Dirección" htmlFor="pj-dir" required className="sm:col-span-2">
            <Input id="pj-dir" value={f.direccion} onChange={set('direccion')} required />
          </Field>
          <Field label="Celular" htmlFor="pj-cel" required>
            <Input id="pj-cel" value={f.celular} onChange={set('celular')} required />
          </Field>
          <Field label="Correo" htmlFor="pj-correo" required hint="Aquí llegarán sus credenciales de acceso">
            <Input id="pj-correo" type="email" value={f.correo} onChange={set('correo')} required />
          </Field>
          <Field label="Inicio de periodo" htmlFor="pj-inicio" required>
            <Input id="pj-inicio" type="date" value={f.fecha_inicio_periodo} onChange={set('fecha_inicio_periodo')} required />
          </Field>
          <Field label="Fin de periodo" htmlFor="pj-fin" hint="Opcional">
            <Input id="pj-fin" type="date" value={f.fecha_fin_periodo} onChange={set('fecha_fin_periodo')} />
          </Field>
        </div>
        <p className="text-xs text-institutional-muted">
          Se generará una contraseña temporal y se enviará a ese correo, con 24 horas para cambiarla.
        </p>
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="primary" loading={mutando.isPending}>
            {modo === 'crear' ? 'Crear presidente' : 'Confirmar reemplazo'}
          </Button>
        </div>
      </form>
    </Modal>
  )
}
