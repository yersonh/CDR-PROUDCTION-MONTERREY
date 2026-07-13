import { createContext, useCallback, useContext, useRef, useState } from 'react'
import { Bell, X } from 'lucide-react'

interface ToastItem {
  id: number
  title: string
  description?: string
  onClick?: () => void
}

interface ToastContextValue {
  showToast: (toast: Omit<ToastItem, 'id'>) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast debe usarse dentro de <ToastProvider>')
  return ctx
}

let nextId = 1

/** Popups efímeros en la esquina superior — usados para avisos que no deben pasar desapercibidos (p. ej. notificaciones nuevas). */
export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<ToastItem[]>([])
  const timers = useRef<Record<number, ReturnType<typeof setTimeout>>>({})

  const dismiss = useCallback((id: number) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
    clearTimeout(timers.current[id])
    delete timers.current[id]
  }, [])

  const showToast = useCallback((toast: Omit<ToastItem, 'id'>) => {
    const id = nextId++
    setToasts((prev) => [...prev, { ...toast, id }])
    timers.current[id] = setTimeout(() => dismiss(id), 8000)
  }, [dismiss])

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <div className="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:left-auto sm:right-4 sm:items-end">
        {toasts.map((t) => (
          <div
            key={t.id}
            role="status"
            className="pointer-events-auto w-full max-w-sm animate-fade-up rounded-xl border border-institutional-border bg-white p-3.5 shadow-lg sm:w-96"
          >
            <div className="flex items-start gap-3">
              <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary">
                <Bell className="h-4 w-4" />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-institutional-text">{t.title}</p>
                {t.description && (
                  <button
                    type="button"
                    onClick={() => { t.onClick?.(); dismiss(t.id) }}
                    className="mt-0.5 block w-full text-left text-sm text-institutional-muted transition-colors hover:text-primary hover:underline"
                  >
                    {t.description}
                  </button>
                )}
              </div>
              <button
                type="button"
                onClick={() => dismiss(t.id)}
                className="shrink-0 rounded-md p-1 text-institutional-muted transition-colors hover:bg-institutional-bg"
                aria-label="Cerrar notificación"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}
