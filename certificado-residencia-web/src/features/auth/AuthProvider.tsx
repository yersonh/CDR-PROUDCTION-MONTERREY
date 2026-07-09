import { useCallback, useEffect, useMemo, useState } from 'react'
import { api, TOKEN_KEY } from '@/lib/api'
import type { User } from '@/types/auth'
import { AuthContext, type AuthContextValue } from './auth-context'

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY))
  const [isLoading, setIsLoading] = useState<boolean>(!!localStorage.getItem(TOKEN_KEY))

  const setSession = useCallback((nextUser: User, nextToken: string) => {
    localStorage.setItem(TOKEN_KEY, nextToken)
    setToken(nextToken)
    setUser(nextUser)
  }, [])

  const clearSession = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY)
    setToken(null)
    setUser(null)
  }, [])

  // Rehidrata la sesión al recargar (valida el token contra /auth/me)
  useEffect(() => {
    if (!token) {
      setIsLoading(false)
      return
    }

    let active = true
    api
      .get<{ data: User }>('/auth/me')
      .then((res) => {
        if (active) setUser(res.data.data)
      })
      .catch(() => {
        if (active) clearSession()
      })
      .finally(() => {
        if (active) setIsLoading(false)
      })

    return () => {
      active = false
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      token,
      isLoading,
      isAuthenticated: !!user && !!token,
      setSession,
      clearSession,
      hasPermission: (permiso) => user?.permisos.includes(permiso) ?? false,
      hasRole: (rol) => user?.roles.includes(rol) ?? false,
    }),
    [user, token, isLoading, setSession, clearSession],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
