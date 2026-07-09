import { useState } from 'react'
import { CheckCircle2, KeyRound, PenTool, Upload } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Field } from '@/components/ui/field'
import { api, getApiErrorMessage } from '@/lib/api'
import { useAuth } from '@/features/auth/useAuth'
import { useChangePassword } from '@/features/auth/usePasswords'

export function PerfilPage() {
  const { user } = useAuth()
  const esAlcalde = user?.roles.includes('alcalde') ?? false

  return (
    <div className="mx-auto max-w-2xl animate-fade-up space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-institutional-text">Mi perfil</h1>
        <p className="text-institutional-muted">{user?.name} · {user?.email}</p>
      </div>

      <CambiarPassword />
      {esAlcalde && <SubirFirma tieneFirma={user?.tiene_firma ?? false} />}
    </div>
  )
}

function CambiarPassword() {
  const change = useChangePassword()
  const [f, setF] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [error, setError] = useState<string>()
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement>) => setF((p) => ({ ...p, [k]: e.target.value }))

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    setError(undefined)
    if (f.password !== f.password_confirmation) { setError('Las contraseñas no coinciden'); return }
    change.mutate(f, { onSuccess: () => setF({ current_password: '', password: '', password_confirmation: '' }) })
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center gap-2">
        <KeyRound className="h-4 w-4 text-primary" /><CardTitle>Cambiar contraseña</CardTitle>
      </CardHeader>
      <CardContent>
        {change.isSuccess && (
          <div className="mb-4 flex items-center gap-2 rounded-lg border border-success/40 bg-green-50 px-4 py-2.5 text-sm text-success">
            <CheckCircle2 className="h-4 w-4" /> {change.data.message}
          </div>
        )}
        {(change.isError || error) && (
          <div className="mb-4 rounded-lg border border-danger/40 bg-red-50 px-4 py-2.5 text-sm text-danger">
            {error ?? getApiErrorMessage(change.error, 'No fue posible cambiar la contraseña.')}
          </div>
        )}
        <form onSubmit={submit} className="space-y-4">
          <Field label="Contraseña actual" htmlFor="cur" required>
            <Input id="cur" type="password" value={f.current_password} onChange={set('current_password')} required />
          </Field>
          <Field label="Nueva contraseña" htmlFor="new" required hint="Mínimo 8 caracteres, con letras y números">
            <Input id="new" type="password" value={f.password} onChange={set('password')} required />
          </Field>
          <Field label="Confirmar nueva contraseña" htmlFor="conf" required>
            <Input id="conf" type="password" value={f.password_confirmation} onChange={set('password_confirmation')} required />
          </Field>
          <Button type="submit" variant="primary" loading={change.isPending}>Actualizar contraseña</Button>
        </form>
      </CardContent>
    </Card>
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
