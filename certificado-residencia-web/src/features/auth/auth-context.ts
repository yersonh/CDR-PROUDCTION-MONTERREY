import { createContext } from 'react'
import type { User } from '@/types/auth'

export interface AuthContextValue {
  user: User | null
  token: string | null
  isLoading: boolean
  isAuthenticated: boolean
  setSession: (user: User, token: string) => void
  clearSession: () => void
  refreshUser: () => Promise<void>
  hasPermission: (permiso: string) => boolean
  hasRole: (rol: string) => boolean
}

export const AuthContext = createContext<AuthContextValue | undefined>(undefined)
