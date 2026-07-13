export interface Notificacion {
  id: number
  tipo: string
  mensaje: string
  solicitud_id: number | null
  leida: boolean
  created_at: string
}
