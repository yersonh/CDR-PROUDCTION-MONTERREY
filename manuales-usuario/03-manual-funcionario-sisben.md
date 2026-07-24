# Manual de Usuario — Funcionario SISBEN
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo el usuario con rol **Funcionario SISBEN** interactúa con el sistema. Tu función principal es **cargar la certificación SISBEN** para las solicitudes cuyo medio de acreditación sea "SISBEN".

---

## 1. Ingreso al sistema

1. Ve a **Inicio de sesión** (`/login`) e ingresa tu correo institucional y contraseña.
2. En tu primer ingreso deberás cambiar la contraseña temporal que recibiste por correo (dentro de las primeras 24 horas).
3. Si olvidaste tu contraseña, usa **"Recuperar contraseña"**.

> 📸 **CAPTURA 1**: pantalla de inicio de sesión.
> Guardar en `manuales-usuario/imagenes/sisben/01-login.png`

El menú lateral muestra: **Inicio, Solicitudes, Mi perfil**.

> 📸 **CAPTURA 2**: menú lateral visible para Funcionario SISBEN.
> Guardar en `manuales-usuario/imagenes/sisben/02-menu-lateral.png`

---

## 2. Panel de inicio (Dashboard)

En **"Inicio"** (`/dashboard`) ves los KPIs generales (Solicitudes, Certificados, Pendientes, Rechazadas, Tiempo promedio) y las gráficas del sistema.

> 📸 **CAPTURA 3**: pantalla del Dashboard.
> Guardar en `manuales-usuario/imagenes/sisben/03-dashboard.png`

---

## 3. Bandeja de solicitudes SISBEN

Entra a **"Solicitudes"** (`/solicitudes`). El sistema te muestra automáticamente solo las solicitudes cuyo **medio de acreditación es SISBEN** — no necesitas filtrar manualmente.

> 📸 **CAPTURA 4**: bandeja de solicitudes filtrada por medio SISBEN.
> Guardar en `manuales-usuario/imagenes/sisben/04-bandeja-sisben.png`

Haz clic en una solicitud para entrar a su detalle.

---

## 4. Cargar la certificación SISBEN

Dentro del detalle de una solicitud (siempre que aún no exista una validación SISBEN registrada), verás el formulario de carga:

1. (Opcional) Pulsa **"Verificar grupo SISBEN en línea"** — abre el portal oficial `sisben.gov.co` en una pestaña nueva para consultar el puntaje/grupo del ciudadano.
2. Adjunta el **archivo de certificación** (el soporte que confirma el grupo/nivel SISBEN del solicitante).
3. Elige el **Resultado**: `Cumple` / `No cumple`.
4. Escribe la **Observación** — es obligatoria si el resultado es "No cumple", para que quede claro por qué.
   - Puedes usar el botón **"Redactar observación con IA"** (ícono de destellos ✨) para que el sistema te ayude a redactar el texto de la observación.
5. Pulsa **"Cargar certificación"**.

> 📸 **CAPTURA 5**: formulario de carga SISBEN completo (botón de verificación en línea, carga de archivo, resultado y observación).
> Guardar en `manuales-usuario/imagenes/sisben/05-formulario-sisben.png`

> 📸 **CAPTURA 6**: botón "Redactar observación con IA" en uso.
> Guardar en `manuales-usuario/imagenes/sisben/06-redactar-ia.png`

Este paso se realiza **una sola vez** por solicitud. Después de cargarlo, la solicitud continúa su curso hacia la revisión de Secretaría (prevalidación).

---

## 5. Mi perfil

En **"Mi perfil"** (`/perfil`) puedes cambiar tu foto y consultar tu información institucional (solo lectura). Este rol **no** requiere cargar firma electrónica, ya que no firma conceptos ni certificados.

> 📸 **CAPTURA 7**: pantalla de "Mi perfil" para Funcionario SISBEN.
> Guardar en `manuales-usuario/imagenes/sisben/07-perfil.png`

---

## 6. Resumen de tu recorrido como Funcionario SISBEN

```
Inicio de sesión
        │
        ▼
Dashboard
        │
        ▼
Solicitudes (filtradas automáticamente por medio = SISBEN)
        │
        ▼
Abrir solicitud → Verificar grupo SISBEN (opcional) → Cargar certificación
        │
        ▼
Queda a la espera del concepto de prevalidación de Secretaría
```
