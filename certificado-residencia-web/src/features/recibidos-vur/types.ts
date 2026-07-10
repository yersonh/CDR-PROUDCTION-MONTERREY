export interface RecibidoVur {
  id: number
  radicado_vur: string
  nombre_completo: string
  tipo_documento: string | null
  numero_identificacion: string | null
  correo: string | null
  celular: string | null
  nombre_original_pdf: string
  estado: 'pendiente' | 'procesado'
  solicitud_id: number | null
  procesado_at: string | null
  created_at: string
}
