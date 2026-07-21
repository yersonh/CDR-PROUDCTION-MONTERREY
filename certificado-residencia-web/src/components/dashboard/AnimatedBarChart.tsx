import { Bar, BarChart, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { motion } from 'framer-motion'
import type { BarItem } from './BarList'

interface TooltipPayloadItem {
  payload: BarItem
}

function BarTooltip({ active, payload }: { active?: boolean; payload?: TooltipPayloadItem[] }) {
  if (!active || !payload?.length) return null
  const it = payload[0].payload
  return (
    <div className="rounded-lg border border-institutional-border bg-white px-3 py-2 text-xs shadow-lg">
      <p className="font-semibold text-institutional-text">{it.label}</p>
      <p className="mt-0.5" style={{ color: it.color ?? '#14306a' }}>
        Total: <span className="font-bold tabular-nums">{it.value}</span>
      </p>
    </div>
  )
}

/** Barras horizontales animadas con tooltip — reemplazo moderno de BarList. */
export function AnimatedBarChart({ items, defaultColor = '#14306a' }: { items: BarItem[]; defaultColor?: string }) {
  if (!items.length) {
    return <p className="py-6 text-center text-sm text-institutional-muted">Sin datos para los filtros seleccionados.</p>
  }

  const height = Math.max(120, items.length * 42)

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4 }}
      style={{ width: '100%', height }}
    >
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={items} layout="vertical" margin={{ top: 4, right: 28, left: 4, bottom: 4 }} barCategoryGap={14}>
          <XAxis type="number" hide allowDecimals={false} />
          <YAxis type="category" dataKey="label" width={132} tick={{ fontSize: 12, fill: '#1e293b' }} axisLine={false} tickLine={false} />
          <Tooltip cursor={{ fill: 'rgba(20,48,106,0.05)' }} content={<BarTooltip />} />
          <Bar dataKey="value" radius={[0, 8, 8, 0]} animationDuration={800} animationEasing="ease-out" label={{ position: 'right', fontSize: 11, fill: '#334155' }}>
            {items.map((it) => <Cell key={it.label} fill={it.color ?? defaultColor} />)}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </motion.div>
  )
}
