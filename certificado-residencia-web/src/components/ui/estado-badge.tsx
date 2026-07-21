import { cn } from '@/lib/utils'

const COLOR_MAP: Record<string, string> = {
  blue: 'bg-blue-100 text-blue-700 border-blue-200',
  indigo: 'bg-indigo-100 text-indigo-700 border-indigo-200',
  amber: 'bg-amber-100 text-amber-700 border-amber-200',
  cyan: 'bg-cyan-100 text-cyan-700 border-cyan-200',
  violet: 'bg-violet-100 text-violet-700 border-violet-200',
  green: 'bg-green-100 text-green-700 border-green-200',
  red: 'bg-red-100 text-red-700 border-red-200',
  slate: 'bg-slate-100 text-slate-600 border-slate-200',
}

export function EstadoBadge({ label, color }: { label: string; color: string }) {
  return (
    <span
      className={cn(
        'inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-semibold',
        COLOR_MAP[color] ?? COLOR_MAP.slate,
      )}
    >
      {label}
    </span>
  )
}

const SEMAFORO: Record<string, { cls: string; label: string }> = {
  green: { cls: 'bg-green-500', label: 'En término' },
  amber: { cls: 'bg-amber-500', label: 'Por vencer' },
  red: { cls: 'bg-red-500', label: 'Crítico / vencido' },
}

export function SemaforoSla({
  semaforo,
  dias,
}: {
  semaforo: 'green' | 'amber' | 'red' | null
  dias: number | null
}) {
  if (!semaforo) return <span className="text-institutional-muted">—</span>
  const s = SEMAFORO[semaforo]
  return (
    // Fondo propio (no solo texto suelto): así se lee igual sobre el fondo
    // fotográfico oscuro del header de la solicitud que sobre una tarjeta
    // blanca en la tabla — antes dependía del color de fondo ambiente y en
    // el header quedaba con poco contraste.
    <span className="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-medium text-institutional-text shadow-sm">
      <span className={cn('h-2.5 w-2.5 rounded-full', s.cls)} aria-hidden />
      {dias !== null ? `${dias} días háb.` : s.label}
    </span>
  )
}
