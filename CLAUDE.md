# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Certificado de Residencia Digital — electronic residency certificate issuance system for the
Alcaldía de Monterrey (Casanare), Colombia. Legal basis: Decreto 1158 de 2019 (adiciona el
Decreto 1066 de 2015, art. 2.3.2.3.1). Built by NexGovIA · Sovereign Data and AI.

Two decoupled projects communicating via REST API + Sanctum token auth:

| Project | Stack | Dev port |
|---|---|---|
| `certificado-residencia-api` | Laravel 13, PHP 8.4, SQLite (dev) / MySQL (prod) | 8000 |
| `certificado-residencia-web` | React 19, Vite, TypeScript, Tailwind v4 | 5173 |

## Commands

### Backend (`certificado-residencia-api`)

```bash
composer install
cp .env.example .env        # ships pre-configured for SQLite in dev
php artisan key:generate
php artisan migrate --seed
php artisan serve                  # http://localhost:8000
composer dev                       # server + queue:listen + pail (logs) + vite, concurrently
php artisan test                   # or: composer test
php artisan test --filter=FlujoTramiteTest   # single test
php artisan migrate:fresh --seed   # reset DB with demo users/solicitudes
```

Switching to MySQL for production: set `DB_CONNECTION=mysql` and `DB_DATABASE` /
`DB_USERNAME` / `DB_PASSWORD` in `.env`, enable `extension=pdo_mysql`.

### Frontend (`certificado-residencia-web`)

```bash
npm install
npm run dev       # http://localhost:5173
npm run build     # tsc -b && vite build
npm run lint      # oxlint
npm run preview
```

### Demo users (password: `password`)

`admin@monterrey-casanare.gov.co` (Super Admin), `alcalde@...` (Alcalde — signs certificates),
`recepcion@...` (Recepcionista), `operador@...` (Operador), `sisben@...` (Funcionario SISBEN),
`jac@...` (Presidente JAC), `ciudadano@example.com` (Ciudadano).

## Architecture

### Domain flow (the core thing to understand)

The whole backend models a 10-step bureaucratic workflow for issuing a residency certificate.
Every module maps to one step:

1. **Solicitud** (request) — citizen submits a 3-step wizard form.
2. **Radicación** — auto-generates `radicado` (`R-AAAA-######`) and `expediente`
   (`EXP-AAAA-######`); SLA clock starts (15 business days, Colombian holidays excluded via
   `App\Support\ColombiaHolidays` + `App\Support\SlaCalculator`).
3. Citizen is notified by email with the radicado for tracking.
4. **Validación de soportes** — supporting docs validated per `MedioAcreditacion` (Electoral /
   SISBEN / JAC-with-QR / Especial).
5. **Prevalidación** — reviewer marks: cumple / requiere subsanación / rechaza.
6. **Firma** — Alcalde's signing inbox; individual or bulk signature.
7. **Certificado** generation — official PDF with electronic signature, QR, code, SHA-256 hash.
8. Automatic delivery — email + portal + expediente.
9. **Consulta pública** (`/verificar`) — anyone can verify a certificate's authenticity by code
   or QR, no auth required.
10. Dashboards — real-time KPIs/charts + audit log (`Auditoria`).

`Solicitud.estado` (enum `App\Enums\EstadoSolicitud`) drives the whole state machine:
`radicada → en_validacion → pendiente_soporte → preaprobada → en_firma → certificada|rechazada`.
Terminal states are `certificada` and `rechazada`. Each transition is recorded as a
`SeguimientoEstado` row (timeline) and an `Auditoria` row (audit log).

Signing is electronic, not PKI: optional signature image + SHA-256 hash + `CR-AAAA-########`
code + QR. No payment gateway (trámite is free).

Supplementary flows: password recovery/reset/change, citizen "subsanación" (re-upload a
support document when a request is `pendiente_soporte` — each re-upload versions the previous
`ExpedienteDocumento`), and admin CRUD for users/dependencias/roles (via spatie/laravel-permission).

### Backend structure (`certificado-residencia-api/app`)

- `Http/Controllers/Api/V1/**` — one controller per resource, thin; business logic lives in
  `Services/`. `Admin/` and `Auth/` subnamespaces group admin and auth endpoints.
- `Services/` — `SolicitudService` (create/radicar), `ValidacionService` (validate/prevalidar/
  subsanar), `CertificadoService` (sign/generate PDF), `DocumentoService` (upload/version
  documents), `QrService`, `RadicadoGenerator`, `AuditService` (writes `Auditoria` rows).
- `Enums/` — `EstadoSolicitud`, `EstadoCertificado`, `TipoCertificado`, `MedioAcreditacion`,
  `ResultadoValidacion`. These are the vocabulary of the state machine; check them before
  adding new states/statuses.
- `DTOs/` — typed payloads for service inputs (e.g. `CreateSolicitudData`).
- `Support/` — `ColombiaHolidays`, `SlaCalculator` — pure logic for the 15-business-day SLA.
- Authorization is permission-based (spatie/laravel-permission), enforced via
  `middleware('permission:xxx')` on routes in `routes/api.php` (e.g. `solicitudes.crear`,
  `validacion.prevalidar`, `firma.firmar`, `admin.usuarios`, `dashboard.ver`, `auditoria.ver`).
  Check `RolePermissionSeeder` for the permission catalogue and role assignments.
- Auth is Sanctum (bearer tokens, not SPA cookie mode) — see `AuthController` under
  `Http/Controllers/Api/V1/Auth`.
- File storage: local disk (`storage/app`) in dev; production sets `LOCAL_STORAGE_PATH`
  to a mounted volume (Railway deploy target). Uploaded supports, signatures, and generated
  PDFs all live there.
- Mail: SMTP in generic prod, but Railway deploy uses `symfony/brevo-mailer` (`MAIL_MAILER=brevo`)
  sent synchronously — no queue worker required for mail in that setup, though
  `QUEUE_CONNECTION=database` + `php artisan queue:work` is supported generally.
- Migrations under `database/migrations` are dated and layered: base Laravel tables first,
  then Sanctum, then spatie permissions, then the domain tables in workflow order
  (`dependencias` → `solicitudes` → `expedientes` → `expediente_documentos` → `validaciones`
  → `certificados` → `seguimiento_estados` → `auditorias`), then two ALTERs (documento
  versioning, user firma_path).

### Frontend structure (`certificado-residencia-web/src`)

- `features/<domain>/` — one folder per domain area (`auth`, `solicitudes`, `firma`,
  `validacion` logic embedded in `solicitudes`, `consulta`, `dashboard`, `auditoria`, `perfil`,
  `admin`, `catalogos`). Each typically has a page component, a `use<Domain>.ts` hook wrapping
  TanStack Query, and sometimes a local `api.ts` / `*-schema.ts` (Zod validation for forms via
  `@hookform/resolvers`).
- `app/router.tsx` — single source of truth for routes. Public: `/login`, `/recuperar`,
  `/restablecer`, `/verificar` (public certificate authenticity check, no auth). Everything
  else nests under `ProtectedRoute` → `AppLayout`.
- `features/auth/` — `AuthProvider` + `auth-context.ts` + `useAuth.ts` hold session state;
  `ProtectedRoute.tsx` gates authenticated routes.
- `lib/api.ts` — the single axios instance. Bearer token from `localStorage` (`crd_token`)
  is attached via request interceptor; a response interceptor clears the token and redirects
  to `/login` on 401. `getApiErrorMessage()` is the standard way to surface API errors in UI.
  Base URL comes from `VITE_API_URL`.
- `components/ui/` — small local design-system primitives (button, card, modal, select,
  estado-badge for status pills, file-upload, etc.) — reuse these instead of introducing a
  new UI library.
- State/data-fetching: TanStack Query throughout `use*` hooks, no separate global store.
- Path alias `@/` maps to `src/` (see `tsconfig.app.json` / `vite.config.ts`).

## Environment / deployment notes

- Frontend and API are deployed separately; keep `VITE_API_URL` (web) and `FRONTEND_URL` /
  `SANCTUM_STATEFUL_DOMAINS` (api) in sync across environments — CORS and Sanctum stateful
  checks depend on this.
- Railway deploy: `Procfile` runs `migrate --force`, then `config:cache` + `route:cache` on
  release, then serves via `php artisan serve`. Requires a persistent volume for
  `LOCAL_STORAGE_PATH` (documents/signatures/PDFs). The certificate shield/branding asset
  ships in code (`resources/branding/`), not on the volume.
