import { useEffect, useState } from 'react'
import { animate } from 'framer-motion'

/** Cuenta hacia el valor final cada vez que cambia (p. ej. al aplicar un filtro nuevo). */
export function AnimatedNumber({ value }: { value: number }) {
  const [display, setDisplay] = useState(0)

  useEffect(() => {
    const controls = animate(0, value, {
      duration: 0.8,
      ease: 'easeOut',
      onUpdate: (v) => setDisplay(Math.round(v)),
    })
    return () => controls.stop()
  }, [value])

  return <>{display.toLocaleString('es-CO')}</>
}
