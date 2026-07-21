import axios from 'axios'

export const TOKEN_KEY = 'crd_token'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Adjunta el token Bearer en cada petición
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Manejo global de sesión expirada
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY)
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login'
      }
    }
    return Promise.reject(err)
  },
)

/** Extrae un mensaje de error legible de una respuesta de la API. */
export function getApiErrorMessage(error: unknown, fallback = 'Ocurrió un error inesperado.'): string {
  if (axios.isAxiosError(error)) {
    const errors = error.response?.data?.errors
    const primerError = errors && Object.values(errors)[0]
    return (
      (Array.isArray(primerError) ? primerError[0] : undefined) ??
      error.response?.data?.message ??
      fallback
    )
  }
  return fallback
}
