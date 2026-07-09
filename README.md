# Certificado de Residencia Digital — Alcaldía de Monterrey (Casanare)

Sistema de gestión y expedición electrónica de certificados de residencia.
Desarrollado por **NexGovIA · Sovereign Data and AI**.

Fundamento: Decreto 1158 de 2019 (adiciona el Decreto 1066 de 2015, art. 2.3.2.3.1).

## Arquitectura

Dos proyectos desacoplados que se comunican por API REST + Sanctum:

| Proyecto | Stack | Puerto dev |
|---|---|---|
| `certificado-residencia-api` | Laravel 13 · PHP 8.4 · SQLite (dev) / MySQL (prod) | 8000 |
| `certificado-residencia-web` | React 19 · Vite · TypeScript · Tailwind v4 | 5173 |

## Requisitos

- PHP 8.4+ y Composer
- Node 20+ y npm
- (Producción) MySQL 8+. En desarrollo se usa SQLite sin instalación.

## Puesta en marcha

### Backend

```bash
cd certificado-residencia-api
composer install
cp .env.example .env        # ya viene con SQLite para dev
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000
```

Para **producción con MySQL**: en `.env` cambia `DB_CONNECTION=mysql` y completa
`DB_DATABASE / DB_USERNAME / DB_PASSWORD`. Habilita `extension=pdo_mysql` en `php.ini`.

### Frontend

```bash
cd certificado-residencia-web
npm install
npm run dev                 # http://localhost:5173
```

## Usuarios demo (password: `password`)

| Rol | Correo |
|---|---|
| Super Administrador | admin@monterrey-casanare.gov.co |
| Alcalde | alcalde@monterrey-casanare.gov.co |
| Recepcionista | recepcion@monterrey-casanare.gov.co |
| Operador | operador@monterrey-casanare.gov.co |
| Funcionario SISBEN | sisben@monterrey-casanare.gov.co |
| Presidente JAC | jac@monterrey-casanare.gov.co |
| Ciudadano | ciudadano@example.com |

## Funcionalidad implementada (completa)

Flujo de 10 pasos del trámite:

1. **Solicitud en línea** — formulario wizard de 3 pasos.
2. **Radicación automática** — radicado `R-AAAA-######`, expediente `EXP-AAAA-######`, SLA de 15 días hábiles (excluye festivos de Colombia).
3. **Notificación al ciudadano** — correo con radicado y seguimiento.
4. **Validación de soportes** — Electoral / SISBEN / JAC (con campos y QR) / Especial.
5. **Prevalidación** — cumple / requiere subsanación / rechaza.
6. **Bandeja de firma del Alcalde** — firma individual y masiva.
7. **Generación del certificado** — PDF oficial con firma electrónica, QR, código y hash SHA-256.
8. **Entrega automática** — correo + portal + expediente electrónico.
9. **Consulta pública de autenticidad** — por código o QR, sin autenticación (`/verificar`).
10. **Dashboards e indicadores** — KPIs y gráficas en tiempo real + bitácora de auditoría.

Complementos:

- **Gestión de contraseñas** — recuperación por correo, restablecimiento y cambio.
- **Subsanación del ciudadano** — re-carga de soporte cuando queda en *Pendiente de soporte*.
- **Versionamiento documental** — cada re-carga versiona el documento anterior.
- **Módulo de administración** — CRUD de usuarios (con rol y dependencia), dependencias y vista de roles/permisos.
- **Firma con imagen** — el Alcalde puede cargar su firma para incrustarla en el PDF.

## Decisiones de alcance

- Firma: electrónica (imagen opcional + hash SHA-256 + código `CR-AAAA-########` + QR). Sin PKI.
- SLA: 15 días hábiles desde la radicación.
- Dependencias: catálogo organizacional (flujo real: Recepción → Despacho Alcalde → Firma).
- Trámite gratuito (sin pasarela de pago).

## Pruebas

```bash
cd certificado-residencia-api
php artisan test            # feature tests del flujo completo
```

## Despliegue a producción

1. **Base de datos MySQL**: en `.env`, `DB_CONNECTION=mysql` + credenciales; habilita
   `extension=pdo_mysql` en `php.ini`. Ejecuta `php artisan migrate --force`.
2. **Correo (SMTP)**: configura `MAIL_MAILER=smtp` y las credenciales `MAIL_*`.
   Las notificaciones (radicación, certificado emitido) se envían al correo del ciudadano.
3. **Colas (opcional, recomendado)**: para enviar correos en segundo plano, cambia
   `QUEUE_CONNECTION=database` y ejecuta un worker: `php artisan queue:work`.
4. **Optimización**: `php artisan config:cache route:cache view:cache`.
5. **Frontend**: `npm run build` en `certificado-residencia-web`; sirve `dist/` detrás de
   un dominio con HTTPS. Ajusta `VITE_API_URL`, y en la API `FRONTEND_URL` /
   `SANCTUM_STATEFUL_DOMAINS` al dominio real.
6. **Almacenamiento**: los documentos se guardan en el disco `local` (`storage/app`).
   Para producción considera `s3` u otro disco configurando `FILESYSTEM_DISK`.

## Reseed de datos demo

```bash
php artisan migrate:fresh --seed   # recrea todo con usuarios y solicitudes demo
```

## Despliegue en Railway

El backend ya está preparado para **correo con Brevo (API HTTP, no SMTP)** y
**almacenamiento en un volumen persistente**. Solo debes crear el servicio, adjuntar
un volumen y definir las variables de entorno.

### 1. Volumen de documentos
Crea un **Volume** en el servicio de la API y móntalo, por ejemplo, en `/data`.
Los expedientes, soportes, firmas y PDFs de certificados se guardarán ahí.

### 2. Variables de entorno del backend (`certificado-residencia-api`)

| Variable | Valor |
|---|---|
| `APP_KEY` | genera con `php artisan key:generate --show` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública de la API (Railway) |
| `FRONTEND_URL` | URL pública del frontend |
| `SANCTUM_STATEFUL_DOMAINS` | dominio del frontend (sin `https://`) |
| `SESSION_DOMAIN` | dominio raíz (o vacío) |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | de tu MySQL de Railway |
| **`LOCAL_STORAGE_PATH`** | **ruta del volumen, p. ej. `/data`** |
| **`MAIL_MAILER`** | **`brevo`** |
| **`BREVO_API_KEY`** | **tu API key de Brevo** (Brevo → SMTP & API → API Keys) |
| `MAIL_FROM_ADDRESS` | remitente verificado en Brevo |
| `MAIL_FROM_NAME` | `Certificado de Residencia Digital` |

> El `Procfile` ejecuta las migraciones y cachea la configuración en cada release,
> y sirve la API. El correo se envía de forma síncrona vía la API de Brevo (no
> requiere worker de colas). El escudo del certificado viaja en el código
> (`resources/branding/`), no en el volumen.

### 3. Variables del frontend (`certificado-residencia-web`)

| Variable | Valor |
|---|---|
| `VITE_API_URL` | `https://<api-railway>/api/v1` |
| `VITE_APP_NAME` | `Certificado de Residencia Digital` |

Build: `npm run build` → sirve `dist/`. Ajusta también en la API el `allowed_origins`
de CORS (usa `FRONTEND_URL`, ya está configurado).
