# Manual de Usuario — Alcalde
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo el **Alcalde** interactúa con el sistema. Tu función principal es **firmar electrónicamente** las solicitudes que ya fueron prevalidadas por Secretaría ("Cumple"), lo que genera el certificado oficial y lo entrega automáticamente al ciudadano. También tienes acceso a Reportes y Dashboard.

---

## 1. Ingreso al sistema

1. Ve a **Inicio de sesión** (`/login`) e ingresa tu correo institucional y contraseña.
2. En tu primer ingreso deberás cambiar la contraseña temporal (primeras 24 horas).
3. Si olvidas tu contraseña, usa **"Recuperar contraseña"**.

> 📸 **CAPTURA 1**: pantalla de inicio de sesión.
> Guardar en `manuales-usuario/imagenes/alcalde/01-login.png`

El menú lateral muestra: **Inicio, Solicitudes, Bandeja de firma, Reportes, Mi perfil**.

> 📸 **CAPTURA 2**: menú lateral visible para Alcalde.
> Guardar en `manuales-usuario/imagenes/alcalde/02-menu-lateral.png`

---

## 2. Antes de firmar: cargar tu firma electrónica

**Este es el primer paso obligatorio.** Sin tu firma cargada, no podrás firmar ningún certificado.

1. Entra a **"Mi perfil"** (`/perfil`).
2. En la sección **"Firma electrónica"**, sube una imagen de tu firma (se recomienda formato PNG con fondo transparente).
3. Esta imagen quedará incrustada en cada certificado que firmes.

> 📸 **CAPTURA 3**: sección "Firma electrónica" en Mi perfil, con la firma ya cargada.
> Guardar en `manuales-usuario/imagenes/alcalde/03-perfil-firma.png`

---

## 3. Panel de inicio (Dashboard)

En **"Inicio"** (`/dashboard`) ves los KPIs generales (Solicitudes, Certificados, Pendientes, Rechazadas, Tiempo promedio) y las gráficas: por estado, por medio de acreditación, tendencia mensual de certificados, y distribución por barrio/vereda/sector. También verás cuántas solicitudes están pendientes de tu firma.

> 📸 **CAPTURA 4**: pantalla del Dashboard.
> Guardar en `manuales-usuario/imagenes/alcalde/04-dashboard.png`

---

## 4. Bandeja de firma

Entra a **"Bandeja de firma"** (`/firma`). Aquí aparecen **todas las solicitudes en estado "Preaprobada"** — es decir, las que Secretaría ya revisó y marcó como "Cumple", listas para tu firma.

### Firma individual desde la bandeja
1. Marca el checkbox de una o varias solicitudes.
2. Pulsa **"Firmar seleccionadas (n)"**.

### Firmar todas de una vez
- Pulsa **"Firmar todas"** — firma en un solo paso todas las solicitudes que están en la bandeja, sin necesidad de marcarlas una por una.

> 📸 **CAPTURA 5**: Bandeja de firma con la lista de solicitudes, checkboxes y los botones "Firmar seleccionadas" / "Firmar todas".
> Guardar en `manuales-usuario/imagenes/alcalde/05-bandeja-firma.png`

Si no has cargado tu firma electrónica (paso 2), ambos botones aparecen deshabilitados con un aviso que te enlaza a Mi perfil.

> 📸 **CAPTURA 6**: aviso de firma faltante en la Bandeja de firma.
> Guardar en `manuales-usuario/imagenes/alcalde/06-aviso-sin-firma.png`

Después de firmar, el sistema te muestra un resumen: cuántas solicitudes se firmaron con éxito y, si alguna falló, el detalle del error por radicado.

> 📸 **CAPTURA 7**: resumen de resultado tras firmar (éxitos y posibles errores).
> Guardar en `manuales-usuario/imagenes/alcalde/07-resultado-firma.png`

### Firma individual desde el detalle de una solicitud

También puedes entrar a **"Solicitudes" → detalle de una solicitud "Preaprobada"** y usar el botón **"Firmar y expedir certificado"** directamente ahí, sin pasar por la bandeja general.

> 📸 **CAPTURA 8**: botón "Firmar y expedir certificado" en el detalle de una solicitud.
> Guardar en `manuales-usuario/imagenes/alcalde/08-firmar-detalle.png`

**Qué ocurre al firmar:** el sistema genera el certificado oficial en PDF con tu firma electrónica, un código QR, un código de verificación (`CR-AAAA-########`) y un hash de integridad (SHA-256); y lo entrega automáticamente al ciudadano por correo y a través del portal de consulta.

---

## 5. Reportes

Entra a **"Reportes"** (`/reportes`). Tiene dos pestañas:

- **Certificado de Residencia**: filtros por fecha, dependencia, estado y medio de acreditación. Muestra indicadores de cumplimiento del SLA (verde/ámbar/rojo/vencidas y % de cumplimiento), gráficas de tendencia, productividad por funcionario y rechazos recientes.
- **VUR — Correspondencia general**: mismos filtros, con indicadores propios de la integración con VUR.

Ambas pestañas permiten **exportar a CSV y a PDF**.

> 📸 **CAPTURA 9**: pestaña "Certificado de Residencia" en Reportes, con filtros y KPIs de SLA.
> Guardar en `manuales-usuario/imagenes/alcalde/09-reportes-certificado.png`

> 📸 **CAPTURA 10**: pestaña "VUR — Correspondencia general" en Reportes.
> Guardar en `manuales-usuario/imagenes/alcalde/10-reportes-vur.png`

---

## 6. Solicitudes (consulta general)

En **"Solicitudes"** (`/solicitudes`) puedes ver todas las solicitudes del sistema, pero a diferencia de Secretaría, no tienes acciones de validación/prevalidación aquí — tu acción propia de este módulo es la firma cuando el estado sea "Preaprobada" (ver sección 4).

---

## 7. Resumen de tu recorrido como Alcalde

```
Inicio de sesión
        │
        ▼
Mi perfil → cargar firma electrónica (una sola vez)
        │
        ▼
Dashboard (ver pendientes de firma)
        │
        ▼
Bandeja de firma → seleccionar / firmar todas
        │
        ▼
Certificado generado y entregado automáticamente al ciudadano
        │
        ▼
Reportes → seguimiento de SLA y productividad
```
