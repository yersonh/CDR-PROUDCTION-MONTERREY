import { useState } from 'react'
import { useLocation, useParams } from 'react-router-dom'
import { AlertTriangle, CheckCircle2, ShieldX } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { FileUpload } from '@/components/ui/file-upload'
import { getApiErrorMessage } from '@/lib/api'
import { useEnviarSubsanacionPublica, useSubsanacionPublicaInfo } from './useSubsanacionPublica'
import escudo from '@/assets/logo-alcaldia.png'

export function SubsanacionPublicaPage() {
  const { id } = useParams<{ id: string }>()
  const location = useLocation()
  const { data, isLoading, isError, error } = useSubsanacionPublicaInfo(id, location.search)
  const enviar = useEnviarSubsanacionPublica(id, location.search)

  const [soporte, setSoporte] = useState<File | null>(null)
  const [justificacion, setJustificacion] = useState('')
  const [errorLocal, setErrorLocal] = useState<string | null>(null)

  const requiereSoporte = data && ['electoral', 'sisben', 'jac'].includes(data.medio_acreditacion)
  const requiereJustificacion = data?.medio_acreditacion === 'especial'
  const yaNoAplica = data && data.estado !== 'pendiente_soporte'

  const submit = () => {
    setErrorLocal(null)
    if (requiereSoporte && !soporte) { setErrorLocal('Debe adjuntar el documento solicitado.'); return }
    if (requiereJustificacion && !justificacion.trim()) { setErrorLocal('Debe escribir la justificación.'); return }

    const fd = new FormData()
    if (soporte) fd.append('soporte', soporte)
    if (justificacion.trim()) fd.append('justificacion', justificacion.trim())
    enviar.mutate(fd)
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#00031e] via-primary-700 to-[#00031e] px-4 py-10">
      <div className="mx-auto max-w-xl">
        <div className="mb-6 flex flex-col items-center text-center text-white">
          <div className="mb-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1.5 ring-2 ring-gold-light ring-offset-2 ring-offset-primary-700">
            <img src={escudo} alt="Escudo Alcaldía de Monterrey" className="h-full w-full rounded-full object-cover" />
          </div>
          <h1 className="text-xl font-bold">Corregir mi solicitud</h1>
          <p className="text-sm text-white/70">Certificado de Residencia Digital · Alcaldía de Monterrey, Casanare</p>
        </div>

        <Card>
          <CardContent className="space-y-4">
            {isLoading && <p className="text-center text-sm text-institutional-muted">Cargando…</p>}

            {isError && (
              <div className="flex items-center gap-3 rounded-xl border border-danger/40 bg-red-50 p-4">
                <ShieldX className="h-8 w-8 shrink-0 text-danger" />
                <div>
                  <p className="font-bold text-institutional-text">Enlace inválido o vencido</p>
                  <p className="text-sm text-institutional-muted">{getApiErrorMessage(error, 'Solicite un nuevo enlace comunicándose con la Alcaldía.')}</p>
                </div>
              </div>
            )}

            {data && yaNoAplica && !enviar.isSuccess && (
              <div className="flex items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4">
                <AlertTriangle className="h-8 w-8 shrink-0 text-warning" />
                <div>
                  <p className="font-bold text-institutional-text">Esta solicitud ya no requiere subsanación</p>
                  <p className="text-sm text-institutional-muted">El radicado {data.radicado} ya fue actualizado. Si tiene dudas, comuníquese con la Alcaldía.</p>
                </div>
              </div>
            )}

            {enviar.isSuccess && (
              <div className="flex items-center gap-3 rounded-xl border border-success/40 bg-green-50 p-4">
                <CheckCircle2 className="h-8 w-8 shrink-0 text-success" />
                <div>
                  <p className="font-bold text-institutional-text">Corrección enviada</p>
                  <p className="text-sm text-institutional-muted">Hemos recibido su documento. Su solicitud volvió a validación.</p>
                </div>
              </div>
            )}

            {data && !yaNoAplica && !enviar.isSuccess && (
              <>
                <div className="rounded-lg bg-institutional-bg px-4 py-3 text-sm">
                  <p><strong>Radicado:</strong> {data.radicado}</p>
                  <p><strong>Solicitante:</strong> {data.nombre_completo}</p>
                  {data.observacion && (
                    <p className="mt-2 text-institutional-muted"><strong>Motivo indicado:</strong> {data.observacion}</p>
                  )}
                </div>

                {requiereSoporte && (
                  <div>
                    <p className="mb-1.5 text-sm font-medium text-institutional-text">Adjunte el documento corregido</p>
                    <FileUpload file={soporte} onChange={setSoporte} />
                  </div>
                )}

                {requiereJustificacion && (
                  <div>
                    <p className="mb-1.5 text-sm font-medium text-institutional-text">Justificación</p>
                    <Textarea value={justificacion} onChange={(e) => setJustificacion(e.target.value)} rows={4} />
                  </div>
                )}

                {(errorLocal || enviar.isError) && (
                  <p className="text-sm font-medium text-danger">
                    {errorLocal ?? getApiErrorMessage(enviar.error, 'No se pudo enviar la corrección.')}
                  </p>
                )}

                <Button variant="primary" className="w-full" onClick={submit} loading={enviar.isPending}>
                  Enviar corrección
                </Button>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
