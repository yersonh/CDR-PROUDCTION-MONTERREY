import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { FileText, Eye, PlusCircle } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select } from '@/components/ui/select'
import { useRecibidosVur, verRecibidoVurPdf } from './api'
import type { RecibidoVur } from './types'

export function RecibidosVurPage() {
  const [estado, setEstado] = useState('pendiente')
  const { data, isLoading } = useRecibidosVur({ estado: estado || undefined })
  const navigate = useNavigate()

  const crearSolicitud = (r: RecibidoVur) => {
    navigate('/solicitudes/nueva', {
      state: {
        precarga: {
          recibido_vur_id: r.id,
          radicado_vur: r.radicado_vur,
          nombre_completo: r.nombre_completo,
          tipo_documento: r.tipo_documento ?? '',
          numero_identificacion: r.numero_identificacion ?? '',
          correo: r.correo ?? '',
          celular: r.celular ?? '',
          direccion: r.direccion ?? '',
          motivo: r.motivo ?? '',
        },
      },
    })
  }

  return (
    <div className="animate-fade-up">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-institutional-text">Recibidos de VUR</h1>
          <p className="text-institutional-muted">
            Solicitudes de Carta de Residencia radicadas desde el sistema de correspondencia (VUR).
          </p>
        </div>
        <div className="w-full sm:w-56">
          <Select value={estado} onChange={(e) => setEstado(e.target.value)}>
            <option value="">Todos</option>
            <option value="pendiente">Pendientes</option>
            <option value="procesado">Procesados</option>
          </Select>
        </div>
      </div>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-institutional-border bg-institutional-bg text-xs uppercase tracking-wide text-institutional-muted">
                <tr>
                  <th className="px-5 py-3 font-semibold">Radicado VUR</th>
                  <th className="px-5 py-3 font-semibold">Solicitante</th>
                  <th className="px-5 py-3 font-semibold">Contacto</th>
                  <th className="px-5 py-3 font-semibold">Recibido</th>
                  <th className="px-5 py-3 font-semibold">Estado</th>
                  <th className="px-5 py-3 font-semibold">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-institutional-border">
                {isLoading && (
                  <tr><td colSpan={6} className="px-5 py-10 text-center text-institutional-muted">Cargando…</td></tr>
                )}
                {!isLoading && data?.data.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-5 py-12 text-center">
                      <FileText className="mx-auto mb-2 h-8 w-8 text-institutional-muted/50" />
                      <p className="text-institutional-muted">No hay recibidos para mostrar.</p>
                    </td>
                  </tr>
                )}
                {data?.data.map((r) => (
                  <tr key={r.id} className="transition-colors hover:bg-primary-50/40">
                    <td className="px-5 py-3 font-semibold text-primary">{r.radicado_vur}</td>
                    <td className="px-5 py-3">
                      <p className="font-medium text-institutional-text">{r.nombre_completo}</p>
                      <p className="text-xs text-institutional-muted">{r.numero_identificacion}</p>
                    </td>
                    <td className="px-5 py-3 text-institutional-muted">
                      <p>{r.correo}</p>
                      <p className="text-xs">{r.celular}</p>
                    </td>
                    <td className="px-5 py-3 text-institutional-muted">
                      {new Date(r.created_at).toLocaleString('es-CO', {
                        dateStyle: 'short',
                        timeStyle: 'short',
                      })}
                    </td>
                    <td className="px-5 py-3">
                      <span className={
                        r.estado === 'pendiente'
                          ? 'rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700'
                          : 'rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700'
                      }>
                        {r.estado === 'pendiente' ? 'Pendiente' : 'Procesado'}
                      </span>
                    </td>
                    <td className="px-5 py-3">
                      <div className="flex gap-2">
                        <Button variant="outline" onClick={() => verRecibidoVurPdf(r.id)}>
                          <Eye className="h-4 w-4" /> Ver PDF
                        </Button>
                        {r.estado === 'pendiente' && (
                          <Button variant="primary" onClick={() => crearSolicitud(r)}>
                            <PlusCircle className="h-4 w-4" /> Crear solicitud
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {data && data.meta.total > 0 && (
        <p className="mt-3 text-xs text-institutional-muted">
          {data.meta.total} recibido(s) · página {data.meta.current_page} de {data.meta.last_page}
        </p>
      )}
    </div>
  )
}
