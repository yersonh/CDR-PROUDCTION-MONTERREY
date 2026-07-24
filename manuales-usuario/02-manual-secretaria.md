# Manual de Usuario — Secretaría
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo el usuario con rol **Secretaría** interactúa con el sistema. Este rol reúne las funciones de recepción y validación general de las solicitudes: revisa el certificado electoral cuando aplica, y emite el **concepto de prevalidación** (cumple / requiere subsanación / rechaza) que decide si un trámite pasa a la firma del Alcalde.

---

## 1. Ingreso al sistema

1. Ve a la pantalla de **Inicio de sesión** (`/login`) e ingresa tu correo institucional y contraseña.
2. Si es tu primer ingreso, el sistema te habrá enviado una contraseña temporal por correo; deberás cambiarla dentro de las primeras 24 horas (pantalla **"Cambiar contraseña temporal"**, que bloquea el resto del sistema hasta que la completes).
3. Si olvidaste tu contraseña, usa **"Recuperar contraseña"** desde la pantalla de login.

> 📸 **CAPTURA 1**: pantalla de inicio de sesión.
> Guardar en `manuales-usuario/imagenes/secretaria/01-login.png`

> 📸 **CAPTURA 2**: pantalla de cambio de contraseña temporal (primer ingreso).
> Guardar en `manuales-usuario/imagenes/secretaria/02-cambio-password.png`

Una vez dentro, verás el menú lateral con: **Inicio, Solicitudes, Auditoría (si aplica), Mi perfil**.

> 📸 **CAPTURA 3**: menú lateral / sidebar visible para Secretaría.
> Guardar en `manuales-usuario/imagenes/secretaria/03-menu-lateral.png`

---

## 2. Panel de inicio (Dashboard)

En **"Inicio"** (`/dashboard`) verás:
- KPIs generales: Solicitudes, Certificados, Pendientes, Rechazadas, Tiempo promedio en días.
- Tu bandeja de trabajo: cuántas solicitudes están en validación, cuántas se radicaron hoy, etc.
- Gráficas: solicitudes por estado, por medio de acreditación, certificados emitidos por mes, y distribución por barrio/vereda/sector.

> 📸 **CAPTURA 4**: pantalla completa del Dashboard con KPIs y gráficas.
> Guardar en `manuales-usuario/imagenes/secretaria/04-dashboard.png`

---

## 3. Bandeja de solicitudes

Entra a **"Solicitudes"** (`/solicitudes`). Como Secretaría ves **todas** las solicitudes del sistema, con filtros de búsqueda por texto y por estado.

> 📸 **CAPTURA 5**: bandeja de solicitudes con la tabla/lista y los filtros.
> Guardar en `manuales-usuario/imagenes/secretaria/05-bandeja-solicitudes.png`

Haz clic sobre cualquier solicitud para entrar a su **detalle** (`/solicitudes/:id`), donde se hace todo el trabajo de gestión del trámite.

---

## 4. Validar el certificado electoral

Cuando el ciudadano eligió **"Electoral"** como medio de acreditación, y aún no se ha registrado una validación electoral, verás el formulario **"Validar certificado electoral"** en el detalle de la solicitud.

Pasos:
1. Revisa el documento electoral adjuntado por el ciudadano.
2. Selecciona el **Resultado**: `Cumple` / `Requiere subsanación` / `Rechazada`.
3. Escribe una **Observación** (obligatoria si no cumple, para que el ciudadano sepa qué corregir).
4. Pulsa **"Registrar validación"**.

> 📸 **CAPTURA 6**: formulario de validación electoral con el selector de resultado y observación.
> Guardar en `manuales-usuario/imagenes/secretaria/06-validar-electoral.png`

---

## 5. Prevalidar la solicitud (concepto oficial)

Este es tu paso más importante. Aparece **solo cuando la solicitud está en estado "En validación"**, y decide si el trámite avanza hacia la firma del Alcalde.

1. Abre la solicitud y revisa todos los documentos y soportes cargados (identidad, electoral/SISBEN/JAC según el medio).
2. En el formulario **"Prevalidar"**, elige el concepto:
   - **Cumple** → la solicitud pasa a "Preaprobada" y queda lista para la firma del Alcalde. Tú quedas registrado como quien **proyectó** el certificado.
   - **Requiere subsanación** → debes indicar **qué documento** debe corregir el ciudadano. La solicitud pasa a "Pendiente de soporte" y se le notifica al ciudadano (correo con enlace para corregir).
   - **Rechaza** → la solicitud pasa a estado terminal "Rechazada".
3. Pulsa **"Emitir concepto"**.

> 📸 **CAPTURA 7**: formulario de prevalidación con las tres opciones de concepto.
> Guardar en `manuales-usuario/imagenes/secretaria/07-prevalidar.png`

> ⚠️ **Importante**: si eliges "Cumple" y **no tienes tu firma cargada en tu perfil**, el botón "Emitir concepto" aparecerá deshabilitado con un aviso. Debes ir primero a **Mi perfil → Firma electrónica** y cargar tu imagen de firma (ver sección 7).

> 📸 **CAPTURA 8**: aviso de botón deshabilitado por falta de firma cargada.
> Guardar en `manuales-usuario/imagenes/secretaria/08-aviso-sin-firma.png`

---

## 6. Recibidos de VUR (solo consulta)

Si tu usuario tiene habilitado el menú **"Recibidos de VUR"** (`/recibidos-vur`), es una bandeja **de solo lectura** con las solicitudes de Carta de Residencia que llegan desde el sistema de correspondencia VUR.

Ahí puedes:
- **"Ver PDF"**: abrir el documento recibido.
- **"Ver solicitud"**: solo si ese registro de VUR ya quedó vinculado a una solicitud del CDR.

> ⚠️ Actualmente esta pantalla no permite radicar ni crear una solicitud manualmente desde aquí — es únicamente informativa. Si tu Alcaldía necesita ese flujo, debe solicitarse como mejora al equipo de desarrollo.

> 📸 **CAPTURA 9**: bandeja de "Recibidos de VUR".
> Guardar en `manuales-usuario/imagenes/secretaria/09-recibidos-vur.png`

---

## 7. Mi perfil

En **"Mi perfil"** (`/perfil`) puedes:
- Cambiar tu foto de perfil.
- Ver tu información institucional (cargo, dependencia, teléfono, correo, fecha de vinculación) — es de solo lectura, viene del sistema Core.
- **Cargar o reemplazar tu firma electrónica** (sección exclusiva de Secretaría y Alcalde): sube una imagen, preferiblemente en formato PNG. Esta firma se incrusta en los conceptos y certificados que emitas.

> 📸 **CAPTURA 10**: pantalla de "Mi perfil" mostrando la sección de Firma electrónica.
> Guardar en `manuales-usuario/imagenes/secretaria/10-perfil-firma.png`

---

## 8. Resumen de tu recorrido como Secretaría

```
Inicio de sesión
        │
        ▼
Dashboard (KPIs de tu bandeja)
        │
        ▼
Solicitudes → abrir una solicitud
        │
        ├── Si es Electoral → Validar certificado electoral
        │
        ▼
Prevalidar (Cumple / Requiere subsanación / Rechaza)
        │
        ├── Cumple → pasa a "Preaprobada" (queda para firma del Alcalde)
        ├── Requiere subsanación → notifica al ciudadano
        └── Rechaza → estado terminal
```
