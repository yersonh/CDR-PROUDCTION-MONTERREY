import { useState } from 'react'
import { Navigate } from 'react-router-dom'
import { Check, Lock, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getApiErrorMessage } from '@/lib/api'
import { cn } from '@/lib/utils'
import { useAuth } from './useAuth'
import { AuthShell } from './AuthShell'
import { useChangePassword } from './usePasswords'

interface Regla {
  label: string
  test: (v: string) => boolean
}

const REGLAS: Regla[] = [
  { label: 'Mínimo 8 caracteres', test: (v) => v.length >= 8 },
  { label: 'Al menos una mayúscula y una minúscula', test: (v) => /[a-z]/.test(v) && /[A-Z]/.test(v) },
  { label: 'Al menos un número', test: (v) => /\d/.test(v) },
]

export function CambiarPasswordTemporalPage() {
  const { user, refreshUser } = useAuth()
  const cambiar = useChangePassword()
  const [currentPassword, setCurrentPassword] = useState('')
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [done, setDone] = useState(false)

  // Si el usuario no tiene pendiente el cambio, esta pantalla no aplica.
  if (user && !user.must_change_password) {
    return <Navigate to="/dashboard" replace />
  }

  const reglasFallidas = REGLAS.filter((r) => !r.test(password))
  const coincide = confirm.length > 0 && password === confirm
  const igualALaActual = password.length > 0 && currentPassword.length > 0 && password === currentPassword
  const puedeEnviar = reglasFallidas.length === 0 && coincide && !igualALaActual && currentPassword.length > 0

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!puedeEnviar) return

    cambiar.mutate(
      { current_password: currentPassword, password, password_confirmation: confirm },
      { onSuccess: async () => { await refreshUser(); setDone(true) } },
    )
  }

  if (done) {
    return <Navigate to="/dashboard" replace />
  }

  return (
    <AuthShell title="Debe cambiar su contraseña" subtitle="Por seguridad, su contraseña temporal debe reemplazarse antes de continuar.">
      <form onSubmit={submit} className="space-y-5">
        {cambiar.isError && (
          <div role="alert" className="rounded-lg border border-danger/40 bg-danger/15 px-4 py-3 text-sm text-red-100">
            {getApiErrorMessage(cambiar.error, 'No fue posible cambiar la contraseña.')}
          </div>
        )}

        <PasswordField
          id="current"
          label="Contraseña temporal (recibida por correo)"
          value={currentPassword}
          onChange={setCurrentPassword}
          autoComplete="current-password"
        />
        <PasswordField id="password" label="Nueva contraseña" value={password} onChange={setPassword} autoComplete="new-password" />
        <PasswordField id="confirm" label="Confirmar nueva contraseña" value={confirm} onChange={setConfirm} autoComplete="new-password" />

        <ul className="space-y-1">
          {REGLAS.map((r) => {
            const ok = r.test(password)
            return (
              <li key={r.label} className={cn('flex items-center gap-1.5 text-xs', ok ? 'text-success' : 'text-white/60')}>
                {ok ? <Check className="h-3.5 w-3.5" /> : <X className="h-3.5 w-3.5" />}
                {r.label}
              </li>
            )
          })}
          {confirm.length > 0 && (
            <li className={cn('flex items-center gap-1.5 text-xs', coincide ? 'text-success' : 'text-danger')}>
              {coincide ? <Check className="h-3.5 w-3.5" /> : <X className="h-3.5 w-3.5" />}
              {coincide ? 'Las contraseñas coinciden' : 'Las contraseñas no coinciden'}
            </li>
          )}
          {igualALaActual && (
            <li className="flex items-center gap-1.5 text-xs text-danger">
              <X className="h-3.5 w-3.5" /> La nueva contraseña no puede ser igual a la actual
            </li>
          )}
        </ul>

        <Button type="submit" variant="gold" size="lg" className="w-full" loading={cambiar.isPending} disabled={!puedeEnviar}>
          Cambiar contraseña
        </Button>
      </form>
    </AuthShell>
  )
}

function PasswordField({
  id, label, value, onChange, autoComplete,
}: { id: string; label: string; value: string; onChange: (v: string) => void; autoComplete: string }) {
  return (
    <div className="space-y-1.5">
      <Label htmlFor={id} className="text-white/90">{label}</Label>
      <div className="relative">
        <Lock className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
        <Input
          id={id}
          type="password"
          className="pl-9"
          autoComplete={autoComplete}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          required
        />
      </div>
    </div>
  )
}
