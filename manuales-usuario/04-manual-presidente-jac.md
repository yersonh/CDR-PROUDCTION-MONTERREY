# Manual de Usuario — Presidente de Junta de Acción Comunal (JAC)
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo el **Presidente de JAC** interactúa con el sistema. Cada Presidente tiene su propio usuario, vinculado a un **sector (barrio/vereda)** específico, y solo ve las solicitudes de vecinos de ese sector.

---

## 1. Ingreso al sistema

1. Cuando la Alcaldía te registra como Presidente JAC, recibes un correo con tus credenciales de acceso (usuario y contraseña temporal).
2. Ve a **Inicio de sesión** (`/login`) e ingresa con esas credenciales.
3. Debes cambiar la contraseña temporal dentro de las primeras 24 horas.
4. Si olvidas tu contraseña, usa **"Recuperar contraseña"**.

> 📸 **CAPTURA 1**: pantalla de inicio de sesión.
> Guardar en `manuales-usuario/imagenes/jac/01-login.png`

El menú lateral muestra: **Inicio, Solicitudes, Mi perfil**.

> 📸 **CAPTURA 2**: menú lateral visible para Presidente JAC.
> Guardar en `manuales-usuario/imagenes/jac/02-menu-lateral.png`

---

## 2. Panel de inicio (Dashboard)

En **"Inicio"** (`/dashboard`) ves los indicadores generales del sistema y las gráficas (aunque tu bandeja de trabajo real está acotada a tu sector).

> 📸 **CAPTURA 3**: pantalla del Dashboard.
> Guardar en `manuales-usuario/imagenes/jac/03-dashboard.png`

---

## 3. Bandeja de solicitudes de tu sector

Entra a **"Solicitudes"** (`/solicitudes`). El sistema te muestra automáticamente solo las solicitudes que:
- Tienen como medio de acreditación **"JAC"**, y
- Corresponden a tu **sector asignado** (barrio o vereda).

No verás solicitudes de otros sectores ni de otros medios de acreditación.

> 📸 **CAPTURA 4**: bandeja de solicitudes filtrada por tu sector.
> Guardar en `manuales-usuario/imagenes/jac/04-bandeja-jac.png`

Haz clic en una solicitud para entrar a su detalle.

---

## 4. Cargar la certificación JAC

Dentro del detalle de la solicitud (si aún no existe una validación JAC registrada), verás el formulario:

Campos de solo lectura (ya vienen prellenados):
- **Presidente**: tu nombre.
- **Sector**: tu sector asignado.

Campos que debes diligenciar:
1. Adjuntar el **archivo de certificación JAC** (el documento donde certificas la residencia del vecino).
2. **Código de verificación** del documento.
3. **Fecha de expedición**.
4. **Fecha de vencimiento**.
5. **Código QR** (opcional, si tu certificación lo incluye).

> 📸 **CAPTURA 5**: formulario de carga de certificación JAC completo.
> Guardar en `manuales-usuario/imagenes/jac/05-formulario-jac.png`

6. Pulsa **"Cargar certificación JAC"**.

Este paso se realiza **una sola vez** por solicitud. Después de cargarlo, la solicitud continúa hacia la revisión de Secretaría (prevalidación).

---

## 5. Mi perfil

En **"Mi perfil"** (`/perfil`) puedes cambiar tu foto y consultar tu información (solo lectura). Este rol **no** requiere cargar firma electrónica.

> 📸 **CAPTURA 6**: pantalla de "Mi perfil" para Presidente JAC.
> Guardar en `manuales-usuario/imagenes/jac/06-perfil.png`

---

## 6. Qué pasa si termina tu periodo

Cuando tu periodo como Presidente JAC termina, el Super Admin del sistema registra el reemplazo: tu acceso se desactiva automáticamente y el nuevo Presidente recibe credenciales propias vinculadas al mismo sector. No necesitas hacer nada en el sistema para esto — es un trámite administrativo del Super Admin.

---

## 7. Resumen de tu recorrido como Presidente JAC

```
Inicio de sesión
        │
        ▼
Dashboard
        │
        ▼
Solicitudes (filtradas automáticamente: medio = JAC, tu sector)
        │
        ▼
Abrir solicitud → Cargar certificación JAC (archivo + código + fechas)
        │
        ▼
Queda a la espera del concepto de prevalidación de Secretaría
```
