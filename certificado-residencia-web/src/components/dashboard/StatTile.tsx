import { cn } from '@/lib/utils'

interface StatTileProps {
  label: string
  value: string | number
  hint?: string
  icon?: React.ElementType
  accent?: 'primary' | 'success' | 'warning' | 'danger'
}

const ACCENT: Record<NonNullable<StatTileProps['accent']>, string> = {
  primary: 'text-primary',
  success: 'text-success',
  warning: 'text-warning',
  danger: 'text-danger',
}

export function StatTile({ label, value, hint, icon: Icon, accent = 'primary' }: StatTileProps) {
  return (
    <div className="rounded-2xl border border-institutional-border bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between">
        <p className="text-xs font-medium uppercase tracking-wide text-institutional-muted">{label}</p>
        {Icon && <Icon className={cn('h-4 w-4', ACCENT[accent])} aria-hidden />}
      </div>
      <p className={cn('mt-2 text-3xl font-bold tabular-nums', ACCENT[accent])}>{value}</p>
      {hint && <p className="mt-1 text-xs text-institutional-muted">{hint}</p>}
    </div>
  )
}
