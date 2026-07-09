import { useEffect } from 'react'
import { Globe, Shield, Zap, X } from 'lucide-react'
import logoEmpresa from '@/assets/logoEmpresa.png'

export function NexGovIAInfoModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-3 backdrop-blur-sm sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="nexgovia-modal-title"
    >
      <div className="absolute inset-0 bg-black/60" onClick={onClose} aria-hidden />

      <div className="relative z-10 flex max-h-[90vh] w-full max-w-lg animate-fade-up flex-col overflow-hidden rounded-3xl border border-institutional-border bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
        {/* Header */}
        <div className="relative flex min-h-36 shrink-0 items-center overflow-hidden border-b border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-6 py-6 pr-14 sm:px-8">
          <div className="pointer-events-none absolute -left-10 -top-10 h-40 w-40 rounded-full bg-amber-500/10 blur-3xl" aria-hidden />

          <button
            onClick={onClose}
            className="absolute right-4 top-4 z-10 rounded-full border border-white/10 bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"
            aria-label="Cerrar"
          >
            <X size={18} />
          </button>

          <div className="relative z-10 flex w-full items-center gap-5">
            <div className="flex h-20 w-20 shrink-0 transform items-center justify-center rounded-full bg-white p-2.5 shadow-xl ring-4 ring-white/10 transition-transform duration-300 hover:scale-105 sm:h-24 sm:w-24">
              <img src={logoEmpresa} alt="Logo NexGovIA" className="h-full w-full object-contain" />
            </div>
            <div>
              <p id="nexgovia-modal-title" className="text-xs font-semibold leading-relaxed text-white sm:text-sm">
                Plataforma desarrollada por <span className="text-amber-400">NexGovIA S.A.S.®</span>
              </p>
              <p className="mt-0.5 text-[11px] leading-relaxed text-slate-300 sm:text-xs">
                Asesores <span className="font-semibold text-indigo-300">e-Governance Solutions</span> para Entidades Públicas.
              </p>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="custom-scrollbar flex-1 overflow-y-auto p-6 sm:p-8">
          <div className="mb-6 text-sm leading-relaxed text-institutional-muted dark:text-slate-300">
            <p className="mb-4">
              NexGovIA es una firma líder en consultoría y desarrollo tecnológico, especializada en la
              implementación de soluciones de Inteligencia Artificial para la administración pública,
              mediante la automatización de procesos de sus actividades administrativas incursas en
              cumplimientos normativos.
            </p>
            <p>
              Diseñamos ecosistemas digitales que permiten a las organizaciones gubernamentales operar
              con mayor transparencia, agilidad y eficiencia, conectando mejor con las necesidades del
              ciudadano moderno.
            </p>
          </div>

          <div className="mb-6 grid grid-cols-1 gap-4 sm:mb-8 sm:grid-cols-3">
            <div className="rounded-2xl bg-indigo-50/50 p-4 dark:bg-slate-800/50">
              <div className="mb-2 text-indigo-500"><Globe size={20} /></div>
              <h4 className="mb-1 text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Alcance</h4>
              <p className="text-[11px] text-gray-500 dark:text-slate-400">Sistemas escalables a nivel nacional.</p>
            </div>
            <div className="rounded-2xl bg-indigo-50/50 p-4 dark:bg-slate-800/50">
              <div className="mb-2 text-purple-500"><Shield size={20} /></div>
              <h4 className="mb-1 text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">Seguridad</h4>
              <p className="text-[11px] text-gray-500 dark:text-slate-400">Protección de datos de alto nivel.</p>
            </div>
            <div className="rounded-2xl bg-indigo-50/50 p-4 dark:bg-slate-800/50">
              <div className="mb-2 text-amber-500"><Zap size={20} /></div>
              <h4 className="mb-1 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">IA</h4>
              <p className="text-[11px] text-gray-500 dark:text-slate-400">Automatización inteligente de procesos.</p>
            </div>
          </div>

          <div className="mb-6 flex flex-col items-center justify-center gap-2 sm:mb-8 sm:flex-row sm:gap-3">
            <p className="text-sm font-medium text-gray-500 dark:text-slate-400">Conoce más sobre nosotros en:</p>
            <a
              href="https://nexgovia.com"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 transition-all duration-300 hover:bg-indigo-100 hover:shadow-sm dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 dark:hover:shadow-[0_0_10px_rgba(99,102,241,0.1)]"
            >
              <Globe size={14} />
              nexgovia.com
            </a>
          </div>

          <div className="flex flex-col gap-4">
            <button
              onClick={onClose}
              className="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-900 py-3 font-semibold text-white shadow-lg shadow-indigo-950/20 transition-all duration-300 hover:from-indigo-700 hover:to-indigo-950 hover:shadow-indigo-950/40"
            >
              Entendido
            </button>
            <p className="text-center text-[10px] text-gray-400 dark:text-slate-500">
              © 2026 NexGovIA · Transformando el futuro de la gestión pública
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
