export interface EnumOption {
  value: string
  label: string
}

export interface EstadoOption extends EnumOption {
  color: string
}

export interface Catalogos {
  tipos_certificado: EnumOption[]
  medios_acreditacion: EnumOption[]
  estados: EstadoOption[]
  tipos_documento: string[]
  dependencias: { id: number; nombre: string }[]
  sectores: { id: number; nombre: string; tipo: 'barrio' | 'vereda'; zona: 'urbana' | 'rural' }[]
  presidentes_jac: { sector_id: number; sector_nombre: string; presidente_nombre: string }[]
}

export interface Documento {
  id: number
  tipo: string
  tipo_label: string
  nombre_original: string
  mime: string | null
  size: number
  hash: string | null
  es_certificado: boolean
  version: number
  vigente: boolean
  subido_at: string
}

export interface Seguimiento {
  id: number
  estado_anterior: string | null
  estado_nuevo: string
  estado_label: string
  color: string
  nota: string | null
  actor?: string | null
  fecha: string
}

export interface Validacion {
  id: number
  tipo: string
  resultado: string | null
  resultado_label: string | null
  observacion: string | null
  meta: {
    codigo_verificacion?: string
    fecha_expedicion?: string
    fecha_vencimiento?: string
    presidente?: string
    sector?: string
    qr?: string
    tipo_documento_solicitado?: string
    tipo_documento_solicitado_label?: string
  } | null
  documento?: Documento
  validado_por?: string | null
  validado_at: string | null
}

export interface Certificado {
  id: number
  consecutivo: string
  codigo_verificacion: string
  hash_documento: string | null
  estado: string
  estado_label: string
  vigente: boolean
  firmado_por?: string | null
  fecha_expedicion: string | null
  vigencia_hasta: string | null
}

export interface Solicitud {
  id: number
  radicado: string
  tipo_certificado: EnumOption
  medio_acreditacion: EnumOption
  estado: EstadoOption
  ciudadano: {
    nombre_completo: string
    tipo_documento: string | null
    numero_identificacion: string
    direccion: string
    correo: string
    celular: string
    barrio_vereda_sector: string
  }
  sector: { id: number; nombre: string } | null
  motivo: string | null
  fecha_radicacion: string
  sla: {
    fecha_limite: string | null
    dias_habiles_restantes: number | null
    semaforo: 'green' | 'amber' | 'red' | null
  }
  dependencia?: string | null
  expediente?: {
    id: number
    codigo: string
    documentos: Documento[]
    creado_at: string
  }
  certificado?: Certificado
  validaciones?: Validacion[]
  seguimientos?: Seguimiento[]
  creado_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}
