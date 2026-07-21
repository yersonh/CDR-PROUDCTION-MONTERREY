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

export interface ReportesVurFiltros {
  desde?: string
  hasta?: string
  estado_id?: number
  tipo_correspondencia_id?: number
  dependencia_destino_id?: number
}

export interface ReportesVurIndicadores {
  filtros: { fecha_desde: string; fecha_hasta: string }
  kpis: { total: number; vencidos: number; promedio_dias_respuesta: number | null; radicados_respondidos: number }
  serie_tiempo: { fecha: string; total: number }[]
  por_estado: { estado_id: number; codigo: string; descripcion: string; color_hex: string; total: number }[]
  por_tipo: { tipo_correspondencia_id: number; descripcion: string; max_dias: number | null; total: number; vencidos: number; cumplimiento_pct: number }[]
  por_dependencia: { dependencia_id: number | string; nombre: string; total: number }[]
  por_operador: { operador_id: number; nombre: string; total: number }[]
  por_funcionario: { funcionario_id: number; nombre: string; total: number }[]
  por_medio_ingreso: { medio_ingreso_id: number; descripcion: string; total: number }[]
  por_procedencia: { valor: string; total: number }[]
  por_manejo: { valor: string; total: number }[]
  sla: {
    respondidos_a_tiempo: number
    respondidos_fuera_plazo: number
    total_respondidos: number
    cerrados_sin_respuesta: number
    anulados: number
    pendientes_en_plazo: number
    pendientes_vencidos: number
    cumplimiento_pct: number | null
    promedio_dias: number | null
  }
}

export interface ReportesVurCatalogos {
  estados: { id: number; codigo: string; descripcion: string; color_hex: string }[]
  tipos_correspondencia: { id: number; descripcion: string }[]
}
