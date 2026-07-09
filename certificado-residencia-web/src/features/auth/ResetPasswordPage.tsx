import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { CheckCircle2, Lock } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getApiErrorMessage } from '@/lib/api'
import { AuthShell } from './AuthShell'
import { useResetPassword } from './usePasswords'

export function ResetPasswordPage() {
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string>()
  const reset = useResetPassword()

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    setError(undefined)
    if (password !== confirm) { setError('Las contraseñas no coinciden'); return }
    reset.mutate({ token, email, password, password_confirmation: confirm })
  }

  return (
    <AuthShell title="Restablecer contraseña" subtitle={email || undefined}>
      {reset.isSuccess ? (
        <div className="space-y-4 text-center">
          <CheckCircle2 className="mx-auto h-12 w-12 text-gold-light" />
          <p className="text-sm text-white/80">{reset.data.message}</p>
          <Link to="/login"><Button variant="gold" className="w-full">Iniciar sesión</Button></Link>
        </div>
      ) : (
        <form onSubmit={submit} className="space-y-5">
          {(reset.isError || error) && (
            <div role="alert" className="rounded-lg border border-danger/40 bg-danger/15 px-4 py-3 text-sm text-red-100">
              {error ?? getApiErrorMessage(reset.error, 'No fue posible restablecer la contraseña.')}
            </div>
          )}
          <PasswordField id="password" label="Nueva contraseña" value={password} onChange={setPassword} />
          <PasswordField id="confirm" label="Confirmar contraseña" value={confirm} onChange={setConfirm} />
          <p className="text-xs text-white/60">Mínimo 8 caracteres, con letras y números.</p>
          <Button type="submit" variant="gold" size="lg" className="w-full" loading={reset.isPending} disabled={!token || !email}>
            Restablecer
          </Button>
        </form>
      )}
    </AuthShell>
  )
}

function PasswordField({ id, label, value, onChange }: { id: string; label: string; value: string; onChange: (v: string) => void }) {
  return (
    <div className="space-y-1.5">
      <Label htmlFor={id} className="text-white/90">{label}</Label>
      <div className="relative">
        <Lock className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
        <Input id={id} type="password" className="pl-9" value={value} onChange={(e) => onChange(e.target.value)} required />
      </div>
    </div>
  )
}
