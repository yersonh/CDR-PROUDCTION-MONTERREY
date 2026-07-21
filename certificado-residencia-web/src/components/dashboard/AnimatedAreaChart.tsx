import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { motion } from 'framer-motion'

export interface TendenciaPunto {
  label: string
  total: number
}

interface TooltipPayloadItem {
  value: number
}

function ChartTooltip({ active, payload, label }: { active?: boolean; payload?: TooltipPayloadItem[]; label?: string }) {
  if (!active || !payload?.length) return null
  return (
    <div className="rounded-lg border border-institutional-border bg-white px-3 py-2 text-xs shadow-lg">
      <p className="font-semibold text-institutional-text">{label}</p>
      <p className="mt-0.5 text-primary-600">
        Radicados: <span className="font-bold tabular-nums">{payload[0].value}</span>
      </p>
    </div>
  )
}

/** Área con degradado y curva suave, animada al montar — reemplazo moderno de TrendBars. */
export function AnimatedAreaChart({ data, color = '#c8a800' }: { data: TendenciaPunto[]; color?: string }) {
  if (!data.length) {
    return <p className="py-6 text-center text-sm text-institutional-muted">Sin datos para los filtros seleccionados.</p>
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4 }}
      style={{ width: '100%', height: 260 }}
    >
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 10, right: 12, left: -16, bottom: 0 }}>
          <defs>
            <linearGradient id="tendenciaFill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={color} stopOpacity={0.35} />
              <stop offset="100%" stopColor={color} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
          <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={{ stroke: '#cbd5e1' }} tickLine={false} />
          <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#64748b' }} axisLine={false} tickLine={false} width={30} />
          <Tooltip content={<ChartTooltip />} />
          <Area
            type="monotone"
            dataKey="total"
            stroke={color}
            strokeWidth={2.5}
            fill="url(#tendenciaFill)"
            dot={{ r: 4, stroke: color, strokeWidth: 2, fill: '#fff' }}
            activeDot={{ r: 6 }}
            animationDuration={900}
            animationEasing="ease-out"
          />
        </AreaChart>
      </ResponsiveContainer>
    </motion.div>
  )
}
