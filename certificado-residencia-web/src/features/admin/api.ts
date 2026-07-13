import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { User } from '@/types/auth'
import type { Paginated } from '@/features/solicitudes/types'

// ---------------- Usuarios ----------------
export function useUsuarios(buscar: string) {
  return useQuery({
    queryKey: ['admin', 'usuarios', buscar],
    queryFn: async () => {
      const { data } = await api.get<Paginated<User>>('/admin/usuarios', { params: { buscar: buscar || undefined } })
      return data
    },
  })
}

export function useGuardarUsuario() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, payload }: { id?: number; payload: Record<string, unknown> }) => {
      const { data } = id
        ? await api.put(`/admin/usuarios/${id}`, payload)
        : await api.post('/admin/usuarios', payload)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'usuarios'] }),
  })
}

export function useToggleUsuario() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await api.post(`/admin/usuarios/${id}/toggle`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'usuarios'] }),
  })
}

// ---------------- Roles ----------------
export interface Rol {
  id: number
  name: string
  usuarios: number
  permisos: string[]
}
export function useRoles() {
  return useQuery({
    queryKey: ['admin', 'roles'],
    queryFn: async () => (await api.get<{ data: Rol[] }>('/admin/roles')).data.data,
  })
}

// ---------------- Dependencias ----------------
export interface DependenciaAdmin {
  id: number
  nombre: string
  codigo: string | null
  activa: boolean
  usuarios_count: number
}
export function useDependencias() {
  return useQuery({
    queryKey: ['admin', 'dependencias'],
    queryFn: async () => (await api.get<{ data: DependenciaAdmin[] }>('/admin/dependencias')).data.data,
  })
}
export function useGuardarDependencia() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, payload }: { id?: number; payload: Record<string, unknown> }) => {
      const { data } = id
        ? await api.put(`/admin/dependencias/${id}`, payload)
        : await api.post('/admin/dependencias', payload)
      return data
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'dependencias'] }),
  })
}
export function useEliminarDependencia() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await api.delete(`/admin/dependencias/${id}`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'dependencias'] }),
  })
}

// ---------------- Sectores ----------------
export interface SectorAdmin {
  id: number
  nombre: string
  tipo: 'barrio' | 'vereda'
  zona: 'urbana' | 'rural'
  activo: boolean
  presidentes_jac_count: number
}
export function useSectoresAdmin() {
  return useQuery({
    queryKey: ['admin', 'sectores'],
    queryFn: async () => (await api.get<{ data: SectorAdmin[] }>('/admin/sectores')).data.data,
  })
}
export function useGuardarSector() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, payload }: { id?: number; payload: Record<string, unknown> }) => {
      const { data } = id
        ? await api.put(`/admin/sectores/${id}`, payload)
        : await api.post('/admin/sectores', payload)
      return data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'sectores'] })
      qc.invalidateQueries({ queryKey: ['catalogos'] })
      qc.invalidateQueries({ queryKey: ['public-catalogos'] })
    },
  })
}

// ---------------- Presidentes JAC ----------------
export interface PresidenteJac {
  id: number
  sector: { id: number; nombre: string }
  nombre_completo: string
  tipo_documento: string
  numero_identificacion: string
  direccion: string
  celular: string
  correo: string | null
  fecha_inicio_periodo: string
  fecha_fin_periodo: string | null
  estado: 'activo' | 'reemplazado'
  user: { id: number; email: string; activo: boolean } | null
}
export function usePresidentesJac(sectorId?: number) {
  return useQuery({
    queryKey: ['admin', 'presidentes-jac', sectorId],
    queryFn: async () => (await api.get<Paginated<PresidenteJac>>('/admin/presidentes-jac', {
      params: { sector_id: sectorId, per_page: 50 },
    })).data,
  })
}
export function useCrearPresidenteJac() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) => (await api.post('/admin/presidentes-jac', payload)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'presidentes-jac'] }),
  })
}
export function useReemplazarPresidenteJac() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await api.post(`/admin/presidentes-jac/${id}/reemplazar`, payload)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'presidentes-jac'] }),
  })
}
