import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate, useLocation, Navigate, Link } from 'react-router-dom'
import { Eye, EyeOff, Lock, Mail, ShieldCheck } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getApiErrorMessage } from '@/lib/api'
import { useAuth } from './useAuth'
import { useLogin } from './useLogin'
import { loginSchema, type LoginFormValues } from './login-schema'
import fondoLogin from '@/assets/fondo-login.png'
import escudo from '@/assets/logo-alcaldia.png'
import { NexGovIAInfoModal } from '@/components/nexgovia-info-modal'

export function LoginPage() {
  const [showPassword, setShowPassword] = useState(false)
  const [showNexGovIA, setShowNexGovIA] = useState(false)
  const navigate = useNavigate()
  const location = useLocation()
  const { isAuthenticated } = useAuth()
  const login = useLogin()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '', remember: true },
  })

  if (isAuthenticated) {
    const from = (location.state as { from?: string })?.from ?? '/dashboard'
    return <Navigate to={from} replace />
  }

  const onSubmit = (values: LoginFormValues) => {
    login.mutate(values, {
      onSuccess: () => {
        const from = (location.state as { from?: string })?.from ?? '/dashboard'
        navigate(from, { replace: true })
      },
    })
  }

  return (
    <main className="relative min-h-screen w-full overflow-hidden">
      {/* Fondo institucional fullscreen */}
      <img
        src={fondoLogin}
        alt=""
        aria-hidden
        className="absolute inset-0 h-full w-full object-cover"
      />
      {/* Overlay oscuro + degradado azul institucional */}
      <div
        className="absolute inset-0 bg-gradient-to-br from-[#00031e]/25 via-primary-700/10 to-[#00031e]/30"
        aria-hidden
      />

      {/* Contenido */}
      <div className="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <section
          className="w-full max-w-md animate-fade-up rounded-2xl border border-white/25 bg-white/10 p-8 shadow-2xl shadow-black/40 backdrop-blur-sm sm:p-10"
          aria-labelledby="login-title"
        >
          {/* Escudo + entidad */}
          <div className="flex flex-col items-center text-center">
            <div className="group mb-4 h-24 w-24 rounded-full bg-gradient-to-br from-gold-light via-gold to-gold-light p-[3px] shadow-lg transition-all duration-300 ease-out hover:scale-105 hover:shadow-[0_0_22px_4px_rgba(200,168,0,0.5)]">
              <div className="flex h-full w-full items-center justify-center rounded-full bg-white p-2 ring-2 ring-white/60">
                <img
                  src={escudo}
                  alt="Escudo de la Alcaldía de Monterrey, Casanare"
                  className="h-full w-full rounded-full object-contain transition-transform duration-300 group-hover:scale-110"
                />
              </div>
            </div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-light">
              Alcaldía de Monterrey · Casanare
            </p>
            <h1 id="login-title" className="mt-2 text-2xl font-bold text-white sm:text-[1.43rem]">
              Certificado de Residencia Digital
            </h1>
            <p className="mt-1 text-sm text-white/70">
              Sistema de gestión y expedición electrónica
            </p>
          </div>

          {/* Formulario */}
          <form onSubmit={handleSubmit(onSubmit)} className="mt-8 space-y-5" noValidate>
            {login.isError && (
              <div
                role="alert"
                className="rounded-lg border border-danger/40 bg-danger/15 px-4 py-3 text-sm text-red-100"
              >
                {getApiErrorMessage(login.error, 'No fue posible iniciar sesión.')}
              </div>
            )}

            <div className="space-y-1.5">
              <Label htmlFor="email" className="text-white/90">
                Correo electrónico
              </Label>
              <div className="relative">
                <Mail
                  className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted"
                  aria-hidden
                />
                <Input
                  id="email"
                  type="email"
                  autoComplete="email"
                  placeholder="usuario@monterrey-casanare.gov.co"
                  className="pl-9"
                  aria-invalid={!!errors.email}
                  aria-describedby={errors.email ? 'email-error' : undefined}
                  {...register('email')}
                />
              </div>
              {errors.email && (
                <p id="email-error" className="text-xs text-red-200">
                  {errors.email.message}
                </p>
              )}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="password" className="text-white/90">
                Contraseña
              </Label>
              <div className="relative">
                <Lock
                  className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-institutional-muted"
                  aria-hidden
                />
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  className="pl-9 pr-10"
                  aria-invalid={!!errors.password}
                  aria-describedby={errors.password ? 'password-error' : undefined}
                  {...register('password')}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-institutional-muted hover:bg-institutional-bg"
                  aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {errors.password && (
                <p id="password-error" className="text-xs text-red-200">
                  {errors.password.message}
                </p>
              )}
            </div>

            <div className="flex items-center justify-between">
              <label className="flex cursor-pointer items-center gap-2 text-sm text-white/80">
                <input
                  type="checkbox"
                  className="h-4 w-4 rounded border-white/40 bg-white/20 accent-gold"
                  {...register('remember')}
                />
                Recordar sesión
              </label>
              <Link
                to="/recuperar"
                className="text-sm font-medium text-gold-light hover:text-gold hover:underline"
              >
                ¿Olvidó su contraseña?
              </Link>
            </div>

            <Button
              type="submit"
              variant="gold"
              size="lg"
              className="w-full"
              loading={login.isPending}
            >
              <ShieldCheck className="h-5 w-5" aria-hidden />
              Ingresar
            </Button>
          </form>

          {/* Footer institucional */}
          <footer className="mt-8 border-t border-white/15 pt-5 text-center">
            <p className="text-[11px] text-white/60">
              Decreto 1158 de 2019 · Gobierno Digital
            </p>
            <button
              type="button"
              onClick={() => setShowNexGovIA(true)}
              className="mt-1 text-[11px] font-medium text-white/70 underline-offset-2 transition hover:text-gold-light hover:underline"
            >
              Desarrollado por NexGovIA · Sovereign Data and AI
            </button>
          </footer>
        </section>
      </div>

      <NexGovIAInfoModal open={showNexGovIA} onClose={() => setShowNexGovIA(false)} />
    </main>
  )
}
