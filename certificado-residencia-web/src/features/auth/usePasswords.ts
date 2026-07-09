import { useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'

export function useForgotPassword() {
  return useMutation({
    mutationFn: async (email: string) => {
      const { data } = await api.post<{ message: string }>('/auth/forgot-password', { email })
      return data
    },
  })
}

export interface ResetPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export function useResetPassword() {
  return useMutation({
    mutationFn: async (payload: ResetPayload) => {
      const { data } = await api.post<{ message: string }>('/auth/reset-password', payload)
      return data
    },
  })
}

export interface ChangePayload {
  current_password: string
  password: string
  password_confirmation: string
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (payload: ChangePayload) => {
      const { data } = await api.post<{ message: string }>('/auth/change-password', payload)
      return data
    },
  })
}
