# Manual de Usuario — Ciudadano
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo cualquier ciudadano puede **solicitar**, **consultar** y **verificar** un Certificado de Residencia, sin necesidad de crear una cuenta ni iniciar sesión.

---

## 1. Antes de empezar

Necesitas tener a la mano:
- Tu número de documento de identidad y una copia digital (foto o escaneo) del documento.
- Tu dirección de residencia y barrio/vereda.
- Correo electrónico y celular donde te puedan contactar.
- Según el medio que uses para acreditar tu residencia:
  - **Electoral**: foto/escaneo de tu certificado electoral.
  - **SISBEN**: foto/escaneo del soporte de antigüedad SISBEN.
  - **JAC (Junta de Acción Comunal)**: no necesitas adjuntar nada en este paso; el trámite lo continúa el Presidente de tu Junta.
- Una impresora o forma de firmar el formulario a mano y volver a digitalizarlo (ver paso 3).

---

## 2. Cómo radicar una solicitud (formulario público)

Ingresa a la dirección pública del sistema y entra a **"Solicitar certificado de residencia"** (`/solicitud-publica`).

> 📸 **CAPTURA 1**: pantalla de bienvenida/landing del formulario público, antes de iniciar el wizard.
> Guardar en `manuales-usuario/imagenes/ciudadano/01-inicio.png`

El formulario es un asistente (wizard) de **3 pasos**. Si cierras el navegador a mitad de camino, el sistema guarda un borrador automáticamente y te preguntará si quieres continuar donde ibas o empezar de nuevo la próxima vez que entres.

### Paso 1 — Tus datos personales

Diligencia:
- Nombre completo
- Tipo de documento (Cédula de Ciudadanía, Tarjeta de Identidad, etc.)
- Número de identificación
- Correo electrónico
- Número de celular (10 dígitos)
- Dirección de residencia
- Barrio / vereda / sector
- Motivo de la solicitud (opcional)

> 📸 **CAPTURA 2**: Paso 1 del formulario con los campos de datos personales.
> Guardar en `manuales-usuario/imagenes/ciudadano/02-paso1-datos.png`

Pulsa **"Siguiente"**.

### Paso 2 — Certificado y soporte

- Elige el **Tipo de certificado** que necesitas.
- Elige el **Medio de acreditación** de tu residencia:
  - **Electoral** → debes adjuntar tu certificado electoral en este mismo paso.
  - **SISBEN** → debes adjuntar el soporte de antigüedad SISBEN en este mismo paso.
  - **JAC** → debes elegir de una lista el sector y el Presidente de Acción Comunal correspondiente a tu barrio/vereda. No adjuntas nada aquí: el Presidente JAC cargará la certificación después de que radiques.
- Adjunta tu **documento de identidad** (obligatorio siempre, sin importar el medio).

> 📸 **CAPTURA 3**: Paso 2 con el selector de "Medio de acreditación" y los campos de carga de archivos.
> Guardar en `manuales-usuario/imagenes/ciudadano/03-paso2-certificado.png`

Pulsa **"Siguiente"**.

### Paso 3 — Confirmación y firma

- El sistema genera automáticamente una **vista previa en PDF** de tu solicitud diligenciada.
- Pulsa **"Descargar para imprimir y firmar"**.
- Imprime el PDF, **fírmalo a mano**, y vuelve a escanearlo o tómale una foto legible.
- Sube ese archivo firmado en el campo **"Documento firmado"** (es obligatorio; sin él no puedes enviar la solicitud).
- Pulsa **"Enviar solicitud"**.

> 📸 **CAPTURA 4**: Paso 3 mostrando la vista previa en PDF y el botón de descarga.
> Guardar en `manuales-usuario/imagenes/ciudadano/04-paso3-preview-pdf.png`

> 📸 **CAPTURA 5**: Paso 3 con el campo de carga del documento firmado y el botón "Enviar solicitud".
> Guardar en `manuales-usuario/imagenes/ciudadano/05-paso3-subir-firmado.png`

### Confirmación final

Al enviar, el sistema te entrega una **referencia de seguimiento** con formato `SP-XXXXXXXX`. **Guárdala** — la necesitarás para consultar el estado de tu trámite. También verás un enlace directo a "Consultar el estado de mi solicitud".

> 📸 **CAPTURA 6**: pantalla de confirmación con la referencia de seguimiento `SP-XXXXXXXX`.
> Guardar en `manuales-usuario/imagenes/ciudadano/06-confirmacion-referencia.png`

Recibirás también un correo electrónico con esta misma referencia.

---

## 3. Cómo consultar el estado de mi solicitud

Ingresa a **"Consultar solicitud"** (`/consultar-solicitud`).

1. Escribe tu referencia de seguimiento (ej. `SP-00000020`).
2. Pulsa **"Consultar"**.

> 📸 **CAPTURA 7**: formulario de consulta con el campo de referencia.
> Guardar en `manuales-usuario/imagenes/ciudadano/07-consultar-form.png`

El sistema te mostrará:
- Estado actual del trámite (con una breve descripción de qué significa)
- Tu referencia
- Nombre del solicitante
- Tipo de certificado
- Fecha de solicitud
- Radicado VUR y Radicado de la Alcaldía (cuando ya existan)

> 📸 **CAPTURA 8**: resultado de la consulta mostrando el estado del trámite.
> Guardar en `manuales-usuario/imagenes/ciudadano/08-consultar-resultado.png`

Si escribes una referencia que no existe, el sistema te lo indicará ("Solicitud no encontrada").

---

## 4. Qué hacer si me piden corregir un documento (subsanación)

Si tu solicitud queda en estado **"Pendiente de soporte"**, es porque un funcionario encontró un problema con alguno de tus documentos y necesita que lo corrijas. Tienes dos formas de hacerlo:

### Opción A — Desde el enlace que te llega por correo

1. Recibirás un correo con un enlace del tipo `/corregir/<id>`. Ábrelo directamente, no necesitas iniciar sesión.
2. Verás el radicado, tu nombre, el documento que se te solicita corregir y la observación del funcionario explicando qué está mal.

> 📸 **CAPTURA 9**: pantalla pública de subsanación mostrando la observación del funcionario.
> Guardar en `manuales-usuario/imagenes/ciudadano/09-subsanacion-publica.png`

3. Adjunta el documento corregido en el campo de carga.
4. Pulsa **"Enviar corrección"**.

Si el enlace ya no aplica (por ejemplo, porque el estado de tu solicitud ya cambió), el sistema te avisará que "Esta solicitud ya no requiere subsanación".

### Opción B — Si ya tienes sesión iniciada en el sistema

Si al radicar el sistema te dio acceso a una cuenta, puedes entrar y, en el detalle de tu solicitud, verás el mismo formulario de **"Subsanar solicitud"** cuando el estado sea "Pendiente de soporte".

> 📸 **CAPTURA 10**: formulario de subsanación dentro del detalle de la solicitud (vista con sesión iniciada).
> Guardar en `manuales-usuario/imagenes/ciudadano/10-subsanacion-sesion.png`

Después de enviar la corrección, tu solicitud vuelve a estado "En validación" para que el funcionario la revise de nuevo.

---

## 5. Cómo verificar la autenticidad de un certificado ya emitido

Ingresa a **"Verificar residencia"** (`/verificar`). Esta pantalla la usa cualquier persona o entidad que reciba tu certificado y quiera comprobar que es auténtico.

1. Escribe el **código de verificación** que aparece impreso en el certificado (formato `ABCD-1234`) o escanea el código QR del documento.

> 📸 **CAPTURA 11**: formulario de verificación con el campo de código.
> Guardar en `manuales-usuario/imagenes/ciudadano/11-verificar-form.png`

2. El sistema muestra:
   - Si el certificado está **vigente** (verde) o **válido pero fuera de vigencia** (ámbar)
   - Consecutivo, estado, tipo de certificado, radicado
   - Titular e identificación
   - Autoridad emisora y quién lo firmó
   - Fecha de expedición y hasta cuándo es vigente
   - Código hash de integridad (SHA-256)
   - Botón **"Descargar certificado"** en PDF

> 📸 **CAPTURA 12**: resultado de la verificación mostrando el badge de vigencia y los datos del certificado.
> Guardar en `manuales-usuario/imagenes/ciudadano/12-verificar-resultado.png`

---

## 6. Resumen de tu recorrido como ciudadano

```
Radicar solicitud (3 pasos) → Referencia SP-XXXXXXXX
        │
        ▼
Consultar estado con la referencia (cuando quieras)
        │
        ├── Si piden corrección → Subsanar (por enlace de correo o con sesión)
        │
        ▼
Certificado emitido → recibes el PDF por correo/portal
        │
        ▼
Cualquiera puede verificar su autenticidad en /verificar
```
