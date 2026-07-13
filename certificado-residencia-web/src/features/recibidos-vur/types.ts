export interface RecibidoVur {
  id: number
  radicado_vur: string
  referencia_cdr: number | null
  nombre_completo: string
  tipo_documento: string | null
  numero_identificacion: string | null
  correo: string | null
  celular: string | null
  direccion: string | null
  motivo: string | null
  nombre_original_pdf: string
  estado: 'pendiente' | 'en_tramite' | 'procesado'
  solicitud_id: number | null
  procesado_at: string | null
  created_at: string
}
