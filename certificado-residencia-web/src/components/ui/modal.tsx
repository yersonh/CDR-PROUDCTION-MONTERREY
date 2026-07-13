import { useEffect } from 'react'
import { createPortal } from 'react-dom'
import { X } from 'lucide-react'

export function Modal({
  open,
  onClose,
  title,
  children,
}: {
  open: boolean
  onClose: () => void
  title: string
  children: React.ReactNode
}) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  // Portal a document.body: si se renderizara inline, "fixed" quedaría
  // posicionado respecto al primer ancestro con transform (p. ej. cualquier
  // página con animate-fade-up, cuyo transform: translateY(0) persiste tras
  // la animación por animation-fill-mode: both) en vez del viewport.
  return createPortal(
    <div className="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} aria-hidden />
      <div className="relative z-10 flex min-h-full items-center justify-center p-4">
        <div className="my-8 w-full max-w-lg animate-fade-up rounded-2xl bg-white shadow-2xl">
          <div className="flex items-center justify-between border-b border-institutional-border bg-primary px-5 py-3.5 text-white rounded-t-2xl">
            <h2 className="font-semibold">{title}</h2>
            <button onClick={onClose} className="rounded-md p-1 hover:bg-white/15" aria-label="Cerrar">
              <X className="h-5 w-5" />
            </button>
          </div>
          <div className="p-5">{children}</div>
        </div>
      </div>
    </div>,
    document.body,
  )
}
