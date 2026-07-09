import { Check } from 'lucide-react'
import { cn } from '@/lib/utils'

export function Stepper({ steps, current }: { steps: string[]; current: number }) {
  return (
    <ol className="flex items-center gap-2">
      {steps.map((label, i) => {
        const done = i < current
        const active = i === current
        return (
          <li key={label} className="flex flex-1 items-center gap-2">
            <div className="flex items-center gap-2">
              <span
                className={cn(
                  'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-colors',
                  done && 'bg-success text-white',
                  active && 'bg-primary text-white ring-4 ring-primary/15',
                  !done && !active && 'bg-institutional-bg text-institutional-muted',
                )}
              >
                {done ? <Check className="h-4 w-4" /> : i + 1}
              </span>
              <span
                className={cn(
                  'hidden text-sm font-medium sm:block',
                  active ? 'text-primary' : 'text-institutional-muted',
                )}
              >
                {label}
              </span>
            </div>
            {i < steps.length - 1 && (
              <span className={cn('h-0.5 flex-1 rounded', done ? 'bg-success' : 'bg-institutional-border')} />
            )}
          </li>
        )
      })}
    </ol>
  )
}
