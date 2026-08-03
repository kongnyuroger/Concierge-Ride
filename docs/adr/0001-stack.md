# 0001 — Stack

## Status
Accepted — 2026-08-03

## Context
Concierge Ride needs a fixed, boring stack for a small team to build a dispatch platform
on: an API-only backend serving JSON to a dispatcher-facing web app, backed by PostgreSQL
for correctness guarantees (constraints, transactions) demanded by NFR-6 (business rules
enforced at the data layer). No customer-facing auth, no server-rendered HTML from the
backend — the frontend owns all UI.

At the time of this decision (2026-08-03) the latest stable versions were:

- Laravel 13 (requires PHP 8.3+)
- SvelteKit 2 on Svelte 5 (SvelteKit 3 was only available as an unstable `@next` preview —
  not eligible under "latest stable")
- PostgreSQL 16
- Node 20 LTS

## Decision
Pinned, exactly-as-installed versions for this bootstrap:

| Component | Version | Notes |
|---|---|---|
| PHP | 8.5.8 (local) | Laravel 13 requires ^8.3; composer.json pins `"php": "^8.3"` |
| Laravel | 13.23.0 | `composer.json`: `"laravel/framework": "^13.8"` |
| Laravel Sanctum | 4.3.3 | Installed via `php artisan install:api` |
| Pest | 4.7.7 | `pestphp/pest`, `pestphp/pest-plugin-laravel` 4.1.0 |
| PostgreSQL | 16 (Docker image `postgres:16`) | Local dev via docker-compose |
| Node | 20.19.6 (LTS) | Toolchain runtime |
| SvelteKit | 2.70.2 | `@sveltejs/kit ^2.63.0` in package.json |
| Svelte | 5.56.8 | |
| TypeScript | 6.0.3 | |
| Vite | 8.2.0 | |
| Tailwind CSS | 4.3.3 | via `@tailwindcss/vite` |
| Vitest | 4.1.10 | |
| ESLint | 10.x + `typescript-eslint` 8.x | |
| Prettier | 3.8.x + `prettier-plugin-svelte`, `prettier-plugin-tailwindcss` | |

Backend is scaffolded API-only: no Blade views, no Vite asset pipeline in `/backend`
(removed `resources/views`, `resources/css`, `resources/js`, `vite.config.js`, and the
Laravel `package.json`). `routes/web.php` is empty; all endpoints live in `routes/api.php`
under the `/api` prefix. CORS defaults (`allowed_origins: ['*']` on `api/*` and
`sanctum/csrf-cookie`) are left as Laravel's framework default for local development.

Local Postgres runs on host port 5435 (not 5432) via docker-compose, because this machine
already runs other Postgres instances/containers on 5432–5434. This is a local-only
convenience; deployed environments will use their own connection details via `.env`.

Both the app database (`concierge_ride`) and a dedicated test database
(`concierge_ride_test`) are created by a docker-entrypoint init script
(`docker/postgres/init/01-test-database.sql`), so Pest's feature tests run against real
PostgreSQL rather than SQLite — required for NFR-6, since DB constraints and behavior
(e.g. check constraints, exclusion constraints) are Postgres-specific and won't be
faithfully exercised by SQLite.

## Consequences
- Any future change to this stack (major framework upgrade, swapping Postgres for another
  DB, adding a server-rendering layer to the backend) needs its own ADR.
- Business-rule tests (Pest) must run against Postgres, not SQLite — CI must provision a
  Postgres service, not rely on an in-memory SQLite shortcut.
- The dev Postgres port (5435) is a local convention only; document it in onboarding but
  don't assume it in CI or deployed environments.
