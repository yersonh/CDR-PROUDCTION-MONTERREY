import { z } from 'zod'

export const solicitudSchema = z
  .object({
    nombre_completo: z.string().min(3, 'Ingrese el nombre completo').max(255),
    tipo_documento: z.string().min(1, 'Seleccione el tipo de documento'),
    numero_identificacion: z.string().min(3, 'Ingrese el número de identificación').max(40),
    direccion: z.string().min(3, 'Ingrese la dirección de residencia').max(255),
    correo: z.string().min(1, 'Ingrese el correo').email('Correo inválido').max(255),
    celular: z
      .string()
      .min(7, 'Ingrese un celular válido')
      .max(10, 'El celular no puede tener más de 10 dígitos')
      .regex(/^\d+$/, 'El celular solo debe contener números'),
    barrio_vereda_sector: z.string().min(2, 'Ingrese el barrio, vereda o sector').max(255),
    // Solo se exige cuando el medio de acreditación es JAC (ver superRefine
    // abajo): el ciudadano elige su presidente/sector de una lista, no lo
    // escribe a mano.
    sector_id: z.string().optional(),
    motivo: z.string().max(1000).optional(),
    tipo_certificado: z.string().min(1, 'Seleccione el tipo de certificado'),
    medio_acreditacion: z.string().min(1, 'Seleccione el medio de acreditación'),
  })
  .superRefine((data, ctx) => {
    if (data.medio_acreditacion === 'jac' && !data.sector_id) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['sector_id'],
        message: 'Seleccione el presidente de Acción Comunal de su sector',
      })
    }

    // La cédula de ciudadanía es siempre numérica y de máximo 10 dígitos en
    // Colombia. Otros documentos (pasaporte, PEP) pueden incluir letras, por
    // eso esta regla solo aplica a CC.
    if (data.tipo_documento === 'CC' && !/^\d{1,10}$/.test(data.numero_identificacion)) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        path: ['numero_identificacion'],
        message: 'La cédula debe ser numérica y de máximo 10 dígitos',
      })
    }
  })

export type SolicitudFormValues = z.infer<typeof solicitudSchema>

/** Campos validados en cada paso del wizard. */
export const STEP_FIELDS: (keyof SolicitudFormValues)[][] = [
  ['nombre_completo', 'tipo_documento', 'numero_identificacion', 'direccion', 'correo', 'celular', 'barrio_vereda_sector', 'motivo'],
  ['tipo_certificado', 'medio_acreditacion', 'sector_id'],
]
