export interface Dependencia {
  id: number
  nombre: string
}

export interface User {
  id: number
  name: string
  email: string
  tipo_documento: string | null
  numero_documento: string | null
  celular: string | null
  activo: boolean
  dependencia?: Dependencia | null
  roles: string[]
  permisos: string[]
  tiene_firma?: boolean
  last_login_at: string | null
}

export interface LoginPayload {
  email: string
  password: string
  remember?: boolean
}

export interface LoginResponse {
  token: string
  token_type: string
  user: User
}
