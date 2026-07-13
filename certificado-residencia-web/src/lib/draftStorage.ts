import type { SolicitudFormValues } from '@/features/solicitudes/solicitud-schema'

/**
 * Autoguardado del formulario público de solicitud en el navegador (IndexedDB),
 * para que el ciudadano no pierda lo diligenciado mientras imprime, firma a
 * mano, escanea y vuelve a subir el documento — ese proceso toma tiempo real
 * y puede implicar cerrar la pestaña o que el celular la descargue en
 * segundo plano. Solo persiste en el mismo navegador/dispositivo, no entre
 * dispositivos distintos.
 */

const DB_NAME = 'cdr-solicitud-publica-draft'
const STORE_NAME = 'draft'
const RECORD_KEY = 'current'
const DB_VERSION = 1
const EXPIRACION_MS = 7 * 24 * 60 * 60 * 1000 // 7 días

export interface SolicitudPublicaDraft {
  formValues: SolicitudFormValues
  documentoIdentidad: File | null
  soporte: File | null
  documentoFirmado: File | null
  step: number
  savedAt: number
}

function abrirDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION)
    req.onupgradeneeded = () => {
      if (!req.result.objectStoreNames.contains(STORE_NAME)) {
        req.result.createObjectStore(STORE_NAME)
      }
    }
    req.onsuccess = () => resolve(req.result)
    req.onerror = () => reject(req.error)
  })
}

/** Nunca lanza — si IndexedDB no está disponible (ej. algunos modos privados), el formulario sigue funcionando, solo sin autoguardado. */
export async function saveDraft(draft: Omit<SolicitudPublicaDraft, 'savedAt'>): Promise<void> {
  try {
    const db = await abrirDb()
    await new Promise<void>((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, 'readwrite')
      tx.objectStore(STORE_NAME).put({ ...draft, savedAt: Date.now() }, RECORD_KEY)
      tx.oncomplete = () => resolve()
      tx.onerror = () => reject(tx.error)
    })
    db.close()
  } catch {
    // Silencioso a propósito — ver comentario del archivo.
  }
}

export async function loadDraft(): Promise<SolicitudPublicaDraft | null> {
  try {
    const db = await abrirDb()
    const draft = await new Promise<SolicitudPublicaDraft | undefined>((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, 'readonly')
      const req = tx.objectStore(STORE_NAME).get(RECORD_KEY)
      req.onsuccess = () => resolve(req.result as SolicitudPublicaDraft | undefined)
      req.onerror = () => reject(req.error)
    })
    db.close()

    if (!draft) return null
    if (Date.now() - draft.savedAt > EXPIRACION_MS) {
      await clearDraft()
      return null
    }
    return draft
  } catch {
    return null
  }
}

export async function clearDraft(): Promise<void> {
  try {
    const db = await abrirDb()
    await new Promise<void>((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, 'readwrite')
      tx.objectStore(STORE_NAME).delete(RECORD_KEY)
      tx.oncomplete = () => resolve()
      tx.onerror = () => reject(tx.error)
    })
    db.close()
  } catch {
    // Silencioso a propósito.
  }
}
