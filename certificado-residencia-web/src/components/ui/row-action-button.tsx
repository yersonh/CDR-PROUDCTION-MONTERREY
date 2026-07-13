import type { LucideIcon } from 'lucide-react'
import { cn } from '@/lib/utils'

interface RowActionButtonProps {
  icon: LucideIcon
  label: string
  onClick: () => void
  variant?: 'default' | 'danger' | 'success'
}

const VARIANT: Record<NonNullable<RowActionButtonProps['variant']>, string> = {
  default: 'text-primary hover:bg-primary-50',
  danger: 'text-danger hover:bg-red-50',
  success: 'text-success hover:bg-green-50',
}

/** Botón de icono para acciones de fila en tablas de administración (editar, activar/desactivar, eliminar, etc.). */
export function RowActionButton({ icon: Icon, label, onClick, variant = 'default' }: RowActionButtonProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      title={label}
      aria-label={label}
      className={cn(
        'inline-flex h-8 w-8 items-center justify-center rounded-lg transition-colors',
        VARIANT[variant],
      )}
    >
      <Icon className="h-4 w-4" />
    </button>
  )
}
