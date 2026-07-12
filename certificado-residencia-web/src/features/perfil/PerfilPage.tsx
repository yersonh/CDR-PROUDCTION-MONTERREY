import { useEffect, useRef, useState } from 'react'
import { Briefcase, CheckCircle2, PenTool, Upload, UserCircle } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { api, getApiErrorMessage } from '@/lib/api'
import { useAuth } from '@/features/auth/useAuth'
import type { Funcionario } from '@/types/auth'

export function PerfilPage() {
  const { user } = useAuth()
  const esAlcalde = user?.roles.includes('alcalde') ?? false

  return (
    <div className="mx-auto max-w-2xl animate-fade-up space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-white">Mi perfil</h1>
        <p className="text-white/70">{user?.name} · {user?.email}</p>
      </div>

      <FotoPerfil tieneFoto={user?.tiene_foto ?? false} />
      <InfoFuncionario funcionario={user?.funcionario} />
      {esAlcalde && <SubirFirma tieneFirma={user?.tiene_firma ?? false} />}
    </div>
  )
}

function FotoPerfil({ tieneFoto }: { tieneFoto: boolean }) {
  const { refreshUser } = useAuth()
  const [file, setFile] = useState<File | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const [estado, setEstado] = useState<'idle' | 'ok' | 'error'>('idle')
  const [msg, setMsg] = useState<string>()
  const objectUrlRef = useRef<string | null>(null)

  // Trae la foto actual (endpoint autenticado, no es una URL pública) cada
  // vez que el usuario tenga una registrada.
  useEffect(() => {
    let active = true
    if (!tieneFoto) {
      setPreviewUrl(null)
      return
    }
    api.get('/perfil/foto', { responseType: 'blob' }).then((res) => {
      if (!active) return
      if (objectUrlRef.current) URL.revokeObjectURL(objectUrlRef.current)
      const url = URL.createObjectURL(res.data as Blob)
      objectUrlRef.current = url
      setPreviewUrl(url)
    }).catch(() => {})
    return () => {
      active = false
    }
  }, [tieneFoto])

  useEffect(() => () => {
    if (objectUrlRef.current) URL.revokeObjectURL(objectUrlRef.current)
  }, [])

  const subir = async () => {
    if (!file) return
    setLoading(true); setMsg(undefined)
    try {
      const fd = new FormData()
      fd.append('foto', file)
      const { data } = await api.post('/perfil/foto', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      setEstado('ok'); setMsg(data.message); setFile(null)
      await refreshUser()
    } catch (e) {
      setEstado('error'); setMsg(getApiErrorMessage(e, 'No fue posible cargar la foto.'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center gap-2">
        <UserCircle className="h-4 w-4 text-primary" /><CardTitle>Foto de perfil</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="flex items-center gap-4">
          <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border border-institutional-border bg-institutional-bg">
            {previewUrl ? (
              <img src={previewUrl} alt="Foto de perfil" className="h-full w-full object-cover" />
            ) : (
              <UserCircle className="h-10 w-10 text-institutional-muted" />
            )}
          </div>
          <div className="flex-1 space-y-3">
            {msg && (
              <div className={`rounded-lg border px-3 py-2 text-sm ${estado === 'error' ? 'border-danger/40 bg-red-50 text-danger' : 'border-success/40 bg-green-50 text-success'}`}>
                {msg}
              </div>
            )}
            <input type="file" accept=".png,.jpg,.jpeg" onChange={(e) => setFile(e.target.files?.[0] ?? null)}
              className="block w-full text-sm text-institutional-muted file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-700" />
            <Button variant="primary" onClick={subir} disabled={!file} loading={loading}>
              <Upload className="h-4 w-4" /> Guardar foto
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function InfoFuncionario({ funcionario }: { funcionario: Funcionario | null | undefined }) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center gap-2">
        <Briefcase className="h-4 w-4 text-primary" /><CardTitle>Información del funcionario</CardTitle>
      </CardHeader>
      <CardContent>
        {funcionario ? (
          <dl className="grid gap-4 sm:grid-cols-2">
            <DatoFuncionario label="Cargo" value={funcionario.cargo} />
            <DatoFuncionario label="Dependencia" value={funcionario.dependencia} />
            <DatoFuncionario label="Teléfono" value={funcionario.telefono} />
            <DatoFuncionario label="Correo institucional" value={funcionario.correo_institucional} />
            <DatoFuncionario
              label="Fecha de vinculación"
              value={funcionario.fecha_vinculacion ? new Date(funcionario.fecha_vinculacion).toLocaleDateString('es-CO') : null}
            />
          </dl>
        ) : (
          <p className="text-sm text-institutional-muted">
            No se encontró información de este usuario en el sistema Core institucional.
          </p>
        )}
      </CardContent>
    </Card>
  )
}

function DatoFuncionario({ label, value }: { label: string; value: string | null | undefined }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wide text-institutional-muted">{label}</dt>
      <dd className="text-sm font-medium text-institutional-text">{value ?? '—'}</dd>
    </div>
  )
}

function SubirFirma({ tieneFirma }: { tieneFirma: boolean }) {
  const [file, setFile] = useState<File | null>(null)
  const [estado, setEstado] = useState<'idle' | 'ok' | 'error'>(tieneFirma ? 'ok' : 'idle')
  const [loading, setLoading] = useState(false)
  const [msg, setMsg] = useState<string>()

  const subir = async () => {
    if (!file) return
    setLoading(true); setMsg(undefined)
    try {
      const fd = new FormData()
      fd.append('firma', file)
      const { data } = await api.post('/perfil/firma', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      setEstado('ok'); setMsg(data.message); setFile(null)
    } catch (e) {
      setEstado('error'); setMsg(getApiErrorMessage(e, 'No fue posible cargar la firma.'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center gap-2">
        <PenTool className="h-4 w-4 text-primary" /><CardTitle>Firma electrónica</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <p className="text-sm text-institutional-muted">
          Cargue una imagen de su firma (PNG con fondo transparente recomendado). Se incrustará en los certificados que firme.
        </p>
        {tieneFirma && estado === 'ok' && !file && (
          <div className="flex items-center gap-2 rounded-lg border border-success/40 bg-green-50 px-4 py-2.5 text-sm text-success">
            <CheckCircle2 className="h-4 w-4" /> Ya tiene una firma registrada. Puede reemplazarla cargando una nueva.
          </div>
        )}
        {msg && (
          <div className={`rounded-lg border px-4 py-2.5 text-sm ${estado === 'error' ? 'border-danger/40 bg-red-50 text-danger' : 'border-success/40 bg-green-50 text-success'}`}>
            {msg}
          </div>
        )}
        <input type="file" accept=".png,.jpg,.jpeg" onChange={(e) => setFile(e.target.files?.[0] ?? null)}
          className="block w-full text-sm text-institutional-muted file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-700" />
        <Button variant="primary" onClick={subir} disabled={!file} loading={loading}>
          <Upload className="h-4 w-4" /> Guardar firma
        </Button>
      </CardContent>
    </Card>
  )
}
