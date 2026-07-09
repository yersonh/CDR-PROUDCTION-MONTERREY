import escudo from '@/assets/logo-alcaldia.png'
import fondo from '@/assets/fondo-login.png'

/** Contenedor visual compartido por las páginas de autenticación públicas. */
export function AuthShell({ title, subtitle, children }: { title: string; subtitle?: string; children: React.ReactNode }) {
  return (
    <main className="relative min-h-screen w-full overflow-hidden">
      <img src={fondo} alt="" aria-hidden className="absolute inset-0 h-full w-full object-cover" />
      <div className="absolute inset-0 bg-gradient-to-br from-[#00031e]/25 via-primary-700/10 to-[#00031e]/30" aria-hidden />
      <div className="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
        <section className="w-full max-w-md animate-fade-up rounded-2xl border border-white/25 bg-white/10 p-8 shadow-2xl shadow-black/40 backdrop-blur-sm sm:p-10">
          <div className="mb-6 flex flex-col items-center text-center">
            <div className="group mb-3 h-20 w-20 rounded-full bg-gradient-to-br from-gold-light via-gold to-gold-light p-[3px] shadow-lg transition-all duration-300 ease-out hover:scale-105 hover:shadow-[0_0_22px_4px_rgba(200,168,0,0.5)]">
              <div className="flex h-full w-full items-center justify-center rounded-full bg-white p-2 ring-2 ring-white/60">
                <img
                  src={escudo}
                  alt="Escudo de la Alcaldía de Monterrey"
                  className="h-full w-full rounded-full object-contain transition-transform duration-300 group-hover:scale-110"
                />
              </div>
            </div>
            <h1 className="text-xl font-bold text-white">{title}</h1>
            {subtitle && <p className="mt-1 text-sm text-white/70">{subtitle}</p>}
          </div>
          {children}
        </section>
      </div>
    </main>
  )
}
