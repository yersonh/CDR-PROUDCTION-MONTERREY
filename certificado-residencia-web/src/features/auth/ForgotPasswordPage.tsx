import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowLeft, CheckCircle2, Mail } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AuthShell } from './AuthShell'
import { useForgotPassword } from './usePasswords'

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const forgot = useForgotPassword()

  return (
    <AuthShell title="Recuperar contraseña" subtitle="Le enviaremos un enlace a su correo">
      {forgot.isSuccess ? (
        <div className="space-y-4 text-center">
          <CheckCircle2 className="mx-auto h-12 w-12 text-gold-light" />
          <p className="text-sm text-white/80">{forgot.data.message}</p>
          <Link to="/login"><Button variant="gold" className="w-full">Volver al inicio</Button></Link>
        </div>
      ) : (
        <form
          onSubmit={(e) => { e.preventDefault(); if (email) forgot.mutate(email) }}
          className="space-y-5"
        >
          <div className="space-y-1.5">
            <Label htmlFor="email" className="text-white/90">Correo electrónico</Label>
            <div className="relative">
              <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted" />
              <Input id="email" type="email" className="pl-9" placeholder="usuario@monterrey-casanare.gov.co" value={email} onChange={(e) => setEmail(e.target.value)} required />
            </div>
          </div>
          <Button type="submit" variant="gold" size="lg" className="w-full" loading={forgot.isPending}>
            Enviar enlace
          </Button>
          <Link to="/login" className="flex items-center justify-center gap-1 text-sm text-gold-light hover:underline">
            <ArrowLeft className="h-4 w-4" /> Volver al inicio de sesión
          </Link>
        </form>
      )}
    </AuthShell>
  )
}
