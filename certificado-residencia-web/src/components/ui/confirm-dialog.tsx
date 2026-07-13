import { useEffect } from 'react'
import { createPortal } from 'react-dom'
import { AlertTriangle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

/**
 * Diálogo de confirmación institucional reutilizable ("¿Está seguro?").
 * Úsalo para cualquier acción destructiva o irreversible (cerrar sesión,
 * eliminar, rechazar, etc.) en lugar de un `window.confirm` nativo.
 */
export function ConfirmDialog({
  open,
  title = '¡Atención!',
  description,
  confirmLabel = 'Sí, continuar',
  cancelLabel = 'Cancelar',
  variant = 'primary',
  loading = false,
  onConfirm,
  onCancel,
}: {
  open: boolean
  title?: string
  description: React.ReactNode
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'primary' | 'danger'
  loading?: boolean
  onConfirm: () => void
  onCancel: () => void
}) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onCancel()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onCancel])

  if (!open) return null

  // Portal a document.body: evita que "fixed" quede atrapado por el
  // transform residual de animate-fade-up en la página que lo invoca.
  return createPortal(
    <div className="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
      <div className="absolute inset-0 bg-black/40" onClick={onCancel} aria-hidden />
      <div className="relative z-10 w-full max-w-sm animate-fade-up rounded-2xl bg-white p-6 text-center shadow-2xl">
        <div
          className={cn(
            'mx-auto flex h-12 w-12 items-center justify-center rounded-full',
            variant === 'danger' ? 'bg-danger/10 text-danger' : 'bg-primary-100 text-primary',
          )}
        >
          <AlertTriangle className="h-6 w-6" aria-hidden />
        </div>
        <h2 id="confirm-dialog-title" className="mt-4 text-lg font-bold text-institutional-text">
          {title}
        </h2>
        <div className="mt-1.5 text-sm text-institutional-muted">{description}</div>

        <div className="mt-6 flex gap-3">
          <Button variant="outline" className="flex-1" onClick={onCancel} disabled={loading}>
            {cancelLabel}
          </Button>
          <Button
            variant={variant === 'danger' ? 'danger' : 'primary'}
            className="flex-1"
            onClick={onConfirm}
            loading={loading}
          >
            {confirmLabel}
          </Button>
        </div>
      </div>
    </div>,
    document.body,
  )
}
