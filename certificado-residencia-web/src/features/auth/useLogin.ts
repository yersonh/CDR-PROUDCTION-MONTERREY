import { useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { LoginPayload, LoginResponse } from '@/types/auth'
import { useAuth } from './useAuth'

export function useLogin() {
  const { setSession } = useAuth()

  return useMutation({
    mutationFn: async (payload: LoginPayload) => {
      const { data } = await api.post<LoginResponse>('/auth/login', payload)
      return data
    },
    onSuccess: (data) => {
      setSession(data.user, data.token)
    },
  })
}
