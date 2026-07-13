export interface ReportesFiltros {
  desde?: string
  hasta?: string
  dependencia_id?: number
  estado?: string
  medio_acreditacion?: string
}

export interface ReportesIndicadores {
  filtros_aplicados: {
    desde: string | null
    hasta: string | null
    dependencia_id: number | null
    estado: string | null
    medio_acreditacion: string | null
  }
  resumen: {
    total: number
    certificadas: number
    rechazadas: number
    pendientes: number
    tiempo_promedio_dias: number | null
  }
  sla: {
    verde: number
    ambar: number
    rojo: number
    vencidas: number
    cumplimiento_pct: number | null
  }
  por_estado: { estado: string; label: string; color: string; total: number }[]
  por_medio: { medio: string; label: string; total: number }[]
  por_dependencia: { dependencia_id: number; nombre: string; total: number }[]
  tendencia: { periodo: string; label: string; total: number }[]
  productividad: { usuario_id: number; nombre: string; validaciones: number; firmas: number; total: number }[]
  rechazos_recientes: { radicado: string; nombre_completo: string; fecha_radicacion: string | null; motivo: string | null }[]
  vur: { recibidos: number; radicados: number; pendientes: number }
}
