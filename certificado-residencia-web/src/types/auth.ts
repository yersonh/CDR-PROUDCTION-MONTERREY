export interface Dependencia {
  id: number
  nombre: string
}

export interface Funcionario {
  cargo: string | null
  dependencia: string | null
  telefono: string | null
  correo_institucional: string | null
  fecha_vinculacion: string | null
}

export interface User {
  id: number
  name: string
  email: string
  tipo_documento: string | null
  numero_documento: string | null
  celular: string | null
  activo: boolean
  must_change_password: boolean
  dependencia?: Dependencia | null
  roles: string[]
  permisos: string[]
  tiene_firma?: boolean
  tiene_foto?: boolean
  funcionario?: Funcionario | null
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
