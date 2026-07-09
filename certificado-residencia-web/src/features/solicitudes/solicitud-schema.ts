import { z } from 'zod'

export const solicitudSchema = z
  .object({
    nombre_completo: z.string().min(3, 'Ingrese el nombre completo').max(255),
    tipo_documento: z.string().min(1, 'Seleccione el tipo de documento'),
    numero_identificacion: z.string().min(3, 'Ingrese el número de identificación').max(40),
    direccion: z.string().min(3, 'Ingrese la dirección de residencia').max(255),
    correo: z.string().min(1, 'Ingrese el correo').email('Correo inválido'),
    celular: z.string().min(7, 'Ingrese un celular válido').max(30),
    barrio_vereda_sector: z.string().min(2, 'Ingrese el barrio, vereda o sector').max(255),
    motivo: z.string().max(1000).optional(),
    tipo_certificado: z.string().min(1, 'Seleccione el tipo de certificado'),
    medio_acreditacion: z.string().min(1, 'Seleccione el medio de acreditación'),
    justificacion_especial: z.string().max(1500).optional(),
  })
  .superRefine((data, ctx) => {
    if (data.medio_acreditacion === 'especial' && !data.justificacion_especial?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['justificacion_especial'],
        message: 'La justificación es obligatoria para un caso especial',
      })
    }
  })

export type SolicitudFormValues = z.infer<typeof solicitudSchema>

/** Campos validados en cada paso del wizard. */
export const STEP_FIELDS: (keyof SolicitudFormValues)[][] = [
  ['nombre_completo', 'tipo_documento', 'numero_identificacion', 'direccion', 'correo', 'celular', 'barrio_vereda_sector', 'motivo'],
  ['tipo_certificado', 'medio_acreditacion', 'justificacion_especial'],
]
