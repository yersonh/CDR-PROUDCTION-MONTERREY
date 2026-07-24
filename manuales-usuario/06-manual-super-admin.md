# Manual de Usuario — Administrador
## Certificado de Residencia Digital (CDR) — Alcaldía de Monterrey, Casanare

Este manual explica cómo el **Administrador** interactúa con el sistema. Este rol tiene acceso a **todas** las funciones (puede operar cualquier bandeja de cualquier otro rol) y además administra usuarios, dependencias, roles, sectores y Presidentes JAC.

---

## 1. Ingreso al sistema

1. Ve a **Inicio de sesión** (`/login`) e ingresa tu correo y contraseña.
2. Si olvidas tu contraseña, usa **"Recuperar contraseña"**.

> 📸 **CAPTURA 1**: pantalla de inicio de sesión.
> Guardar en `manuales-usuario/imagenes/admin/01-login.png`

El menú lateral muestra **todas** las opciones: Inicio, Solicitudes, Recibidos de VUR, Bandeja de firma, Usuarios, Dependencias, Roles, Sectores, Presidentes JAC, Auditoría, Reportes, Mi perfil.

> 📸 **CAPTURA 2**: menú lateral completo visible para Super Admin.
> Guardar en `manuales-usuario/imagenes/admin/02-menu-lateral.png`

---

## 2. Gestión de usuarios

Entra a **"Usuarios"** (`/admin/usuarios`). Verás una tabla con nombre, correo, rol, dependencia y estado (activo/inactivo) de cada usuario del sistema.

> 📸 **CAPTURA 3**: tabla de usuarios.
> Guardar en `manuales-usuario/imagenes/admin/03-tabla-usuarios.png`

### Crear un nuevo usuario
1. Pulsa **"Nuevo usuario"**.
2. Diligencia: Nombre, Correo, Rol (selecciona entre Super Admin, Alcalde, Secretaría, Funcionario SISBEN, Presidente JAC), Celular.
3. La **dependencia** se asigna automáticamente según el rol: los roles de Despacho (Super Admin, Alcalde, Secretaría) quedan fijos en "Despacho del Alcalde"; SISBEN y JAC se registran como funcionarios externos, sin dependencia.
4. Pulsa **"Crear"**.
5. El sistema genera una contraseña temporal y la envía por correo al nuevo usuario, quien tendrá 24 horas para cambiarla.

> 📸 **CAPTURA 4**: modal "Nuevo usuario" con el formulario completo.
> Guardar en `manuales-usuario/imagenes/admin/04-nuevo-usuario.png`

### Editar o (des)activar un usuario
- En cada fila de la tabla tienes las acciones **"Editar"** y **"Activar/Desactivar"**.

> 📸 **CAPTURA 5**: acciones por fila (Editar, Activar/Desactivar) en la tabla de usuarios.
> Guardar en `manuales-usuario/imagenes/admin/05-acciones-usuario.png`

---

## 3. Gestión de dependencias

Entra a **"Dependencias"** (`/admin/dependencias`). Es un **CRUD** simple:
- Crear: Nombre y Código de la dependencia.
- Editar dependencias existentes.
- Eliminar: solo es posible si la dependencia **no tiene usuarios asignados**.

> 📸 **CAPTURA 6**: pantalla de Dependencias con la lista y el formulario de creación/edición.
> Guardar en `manuales-usuario/imagenes/admin/06-dependencias.png`

---

## 4. Roles y permisos (solo consulta)

Entra a **"Roles"** (`/admin/roles`). Es una vista de **solo lectura**: tarjetas por cada rol, mostrando cuántos usuarios lo tienen asignado y los permisos que le corresponden (en formato de etiquetas legibles).

> 📸 **CAPTURA 7**: pantalla de Roles con las tarjetas de cada rol y sus permisos.
> Guardar en `manuales-usuario/imagenes/admin/07-roles.png`

No se pueden editar los permisos de cada rol desde esta pantalla; están definidos en el backend del sistema.

---

## 5. Gestión de sectores (barrios/veredas)

Entra a **"Sectores"** (`/admin/sectores`). CRUD de barrios y veredas del municipio:
- Nombre
- Tipo: `barrio` / `vereda`
- Zona: `urbana` / `rural`
- Estado: activo / inactivo

> 📸 **CAPTURA 8**: pantalla de Sectores con la tabla y el formulario de creación.
> Guardar en `manuales-usuario/imagenes/admin/08-sectores.png`

Estos sectores son los que luego los ciudadanos eligen en el formulario público, y a los que se vinculan los Presidentes JAC.

---

## 6. Gestión de Presidentes JAC

Entra a **"Presidentes JAC"** (`/admin/presidentes-jac`).

### Registrar un nuevo Presidente JAC
1. Pulsa **"Nuevo presidente"** (este botón se deshabilita si ya no quedan sectores libres — solo puede haber **1 presidente activo por sector**).
2. Diligencia: Sector, Nombre completo, Tipo y Número de documento, Dirección, Celular, Correo (ahí le llegarán sus credenciales de acceso), Fecha de inicio y fin del periodo.
3. Pulsa **"Crear"**. El sistema envía las credenciales de acceso al correo registrado.

> 📸 **CAPTURA 9**: modal "Nuevo presidente" con el formulario completo.
> Guardar en `manuales-usuario/imagenes/admin/09-nuevo-presidente-jac.png`

### Reemplazar un Presidente JAC activo
- Sobre un presidente activo, usa la acción **"Reemplazar"**: cierra su periodo actual (desactiva su acceso) y crea uno nuevo vinculado al mismo sector, con credenciales propias.

> 📸 **CAPTURA 10**: acción "Reemplazar" sobre un Presidente JAC activo.
> Guardar en `manuales-usuario/imagenes/admin/10-reemplazar-jac.png`

---

## 7. Auditoría

Entra a **"Auditoría"** (`/auditoria`). Verás una tabla con el histórico de acciones del sistema:
- Fecha
- Tipo de acción (etiquetas: certificado emitido, concepto de prevalidación, validación registrada, cambio de estado, solicitud subsanada, documento versionado)
- Descripción
- Usuario responsable
- Dirección IP

Puedes buscar por descripción o por IP.

> 📸 **CAPTURA 11**: pantalla de Auditoría con la tabla de eventos y el buscador.
> Guardar en `manuales-usuario/imagenes/admin/11-auditoria.png`

---

## 8. Dashboard, Reportes, Solicitudes, Bandeja de firma y Recibidos de VUR

Como Super Admin tienes acceso a **todas** las bandejas operativas descritas en los demás manuales:
- **Dashboard** (`/dashboard`): KPIs y gráficas generales del sistema.
- **Reportes** (`/reportes`): igual que en el manual del Alcalde (pestañas Certificado de Residencia / VUR, con exportación).
- **Solicitudes** (`/solicitudes`): puedes ver, validar (electoral/SISBEN/JAC), prevalidar y gestionar subsanaciones igual que Secretaría/SISBEN/JAC (ver esos manuales para el detalle paso a paso de cada acción).
- **Bandeja de firma** (`/firma`): puedes firmar certificados igual que el Alcalde, si necesitas cubrir esa función (requiere cargar tu firma en Mi perfil primero).
- **Recibidos de VUR** (`/recibidos-vur`): bandeja de solo lectura de solicitudes de Carta de Residencia enviadas por VUR (ver "Ver PDF" / "Ver solicitud"). Actualmente no permite radicar manualmente desde ahí.

> 📸 **CAPTURA 12**: Dashboard visto como Super Admin.
> Guardar en `manuales-usuario/imagenes/admin/12-dashboard.png`

---

## 9. Mi perfil

En **"Mi perfil"** (`/perfil`) puedes cambiar tu foto, ver tu información institucional, y cargar tu firma electrónica si vas a usar la Bandeja de firma.

> 📸 **CAPTURA 13**: pantalla de Mi perfil para Super Admin.
> Guardar en `manuales-usuario/imagenes/admin/13-perfil.png`

---

## 10. Resumen de tu recorrido como Super Admin

```
Inicio de sesión
        │
        ▼
Administración: Usuarios · Dependencias · Roles (consulta) · Sectores · Presidentes JAC
        │
        ▼
Supervisión: Dashboard · Auditoría · Reportes
        │
        ▼
Operación (cuando sea necesario cubrir un rol): Solicitudes · Bandeja de firma · Recibidos de VUR
```
