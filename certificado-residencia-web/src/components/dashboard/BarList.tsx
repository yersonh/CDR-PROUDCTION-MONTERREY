import { cn } from '@/lib/utils'

export interface BarItem {
  label: string
  value: number
  color?: string
}

/** Mapa de colores semánticos de estado → tonos sólidos accesibles. */
export const SEMANTIC_HEX: Record<string, string> = {
  blue: '#3b82f6',
  indigo: '#6366f1',
  amber: '#f59e0b',
  cyan: '#06b6d4',
  violet: '#8b5cf6',
  green: '#16a34a',
  red: '#dc2626',
  slate: '#94a3b8',
}

/**
 * Barras horizontales de magnitud. Una sola tonalidad por defecto (primary),
 * o color por ítem para categorías con identidad semántica (estados).
 */
export function BarList({ items, defaultColor = '#2b5ba8' }: { items: BarItem[]; defaultColor?: string }) {
  const max = Math.max(1, ...items.map((i) => i.value))

  return (
    <ul className="space-y-3">
      {items.map((it) => (
        <li key={it.label} className="grid grid-cols-[9rem_1fr_2.5rem] items-center gap-3 text-sm">
          <span className="truncate text-institutional-text" title={it.label}>{it.label}</span>
          <span className="relative h-2.5 rounded-full bg-institutional-bg" role="img" aria-label={`${it.label}: ${it.value}`}>
            <span
              className={cn('absolute left-0 top-0 h-2.5 rounded-full transition-all')}
              style={{ width: `${(it.value / max) * 100}%`, backgroundColor: it.color ?? defaultColor, minWidth: it.value > 0 ? '0.625rem' : 0 }}
            />
          </span>
          <span className="text-right font-semibold tabular-nums text-institutional-text">{it.value}</span>
        </li>
      ))}
    </ul>
  )
}

/** Mini-tendencia mensual en columnas. */
export function TrendBars({ items }: { items: { label: string; total: number }[] }) {
  const max = Math.max(1, ...items.map((i) => i.total))

  return (
    <div className="flex items-end justify-between gap-2 pt-2" style={{ height: 140 }}>
      {items.map((it) => (
        <div key={it.label} className="flex flex-1 flex-col items-center gap-1.5">
          <span className="text-xs font-semibold tabular-nums text-institutional-text">{it.total || ''}</span>
          <div className="flex w-full items-end justify-center" style={{ height: 90 }}>
            <div
              className="w-full max-w-[2.5rem] rounded-t-md bg-primary-500 transition-all"
              style={{ height: `${(it.total / max) * 100}%`, minHeight: it.total > 0 ? 6 : 2, backgroundColor: it.total > 0 ? '#2b5ba8' : '#e2e8f0' }}
            />
          </div>
          <span className="text-xs capitalize text-institutional-muted">{it.label}</span>
        </div>
      ))}
    </div>
  )
}
