import { useRef, useState } from 'react'
import { FileCheck2, UploadCloud, X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface FileUploadProps {
  file: File | null
  onChange: (file: File | null) => void
  accept?: string
  maxMb?: number
  error?: string
}

export function FileUpload({ file, onChange, accept = '.pdf,.jpg,.jpeg,.png', maxMb = 20, error }: FileUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [drag, setDrag] = useState(false)
  const [localError, setLocalError] = useState<string | null>(null)

  const validateAndSet = (f: File | null) => {
    setLocalError(null)
    if (!f) return onChange(null)
    if (f.size > maxMb * 1024 * 1024) {
      setLocalError(`El archivo supera los ${maxMb} MB`)
      return
    }
    onChange(f)
  }

  if (file) {
    return (
      <div className="flex items-center justify-between rounded-lg border border-success/40 bg-green-50 px-4 py-3">
        <div className="flex items-center gap-3 overflow-hidden">
          <FileCheck2 className="h-5 w-5 shrink-0 text-success" />
          <div className="overflow-hidden">
            <p className="truncate text-sm font-medium text-institutional-text">{file.name}</p>
            <p className="text-xs text-institutional-muted">{(file.size / 1024 / 1024).toFixed(2)} MB</p>
          </div>
        </div>
        <button
          type="button"
          onClick={() => validateAndSet(null)}
          className="rounded-md p-1 text-institutional-muted hover:bg-white"
          aria-label="Quitar archivo"
        >
          <X className="h-4 w-4" />
        </button>
      </div>
    )
  }

  return (
    <div>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => { e.preventDefault(); setDrag(true) }}
        onDragLeave={() => setDrag(false)}
        onDrop={(e) => {
          e.preventDefault()
          setDrag(false)
          validateAndSet(e.dataTransfer.files?.[0] ?? null)
        }}
        className={cn(
          'flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors',
          drag ? 'border-primary-500 bg-primary-50' : 'border-institutional-border bg-institutional-bg hover:border-primary-500',
        )}
      >
        <UploadCloud className="h-8 w-8 text-primary" />
        <p className="text-sm font-medium text-institutional-text">
          Arrastre el archivo o haga clic para seleccionar
        </p>
        <p className="text-xs text-institutional-muted">PDF, JPG o PNG · máx {maxMb} MB</p>
      </button>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        onChange={(e) => validateAndSet(e.target.files?.[0] ?? null)}
      />
      {(localError || error) && (
        <p className="mt-1.5 text-xs font-medium text-danger">{localError || error}</p>
      )}
    </div>
  )
}
