# Concierge Ride — Project Guide

## What this is
An internal dispatch cockpit for a chauffeur / ground-transport business in Cameroon
(Douala & Yaoundé). Customers NEVER log in — human dispatchers capture and confirm
everything. We sell products (Airport transfer, Half-day, Full-day, Monthly) × tiers
(Standard, SUV, Van; Premium is NOT bookable). Drivers are contractors with
individually-negotiated payout rates, so every job carries a customer price AND a driver
payout; margin = the difference.

## Stack (do not change without an ADR in /docs/adr)
- Backend: Laravel (latest stable), PHP 8.x, PostgreSQL. API-only (JSON) — no Blade UI.
- Frontend: SvelteKit (latest stable), TypeScript, Tailwind. Consumes the API.
- Realtime: Laravel Reverb + Laravel Echo (shared board state must appear within seconds).
- Auth: Laravel Sanctum; server-side policies for permissions.
- Roles/permissions: spatie/laravel-permission.
- Job lifecycle: spatie/laravel-model-states (state machine).
- Audit log: spatie/laravel-activitylog.
- Tests: Pest (backend); Vitest + Playwright (frontend).
- Languages: French + English for all customer-facing text.

## Repo layout
- /backend    — Laravel API
- /frontend   — SvelteKit app
- /docs        — the build spec + this guide
- /docs/adr    — architecture decision records, one file per decision

## THE non-negotiable rule (NFR-6)
Every business rule (the BR-* rules in the spec) MUST be enforced at the data layer — a DB
constraint, a model guard/observer, or a service inside a transaction — NEVER only in a
form or the UI. Each rule ships with a test that FAILS if the rule is removed. If a rule can
be bypassed by calling the API or the model directly, it is NOT done.
See /docs/adr/0003-rule-enforcement.md and "How to add a business rule" below.

## Money & history integrity
- Prices come ONLY from the price book — never typed. Overrides require a reason.
- A job captures its price and the driver's payout as stored VALUES at creation time; later
  price-book or rate changes must NOT alter past jobs.
- Records are never hard-deleted — use a status/state column.

## Conventions
- Branch: feature/CR-<n>-short-slug. Conventional commits. One PR per ticket.
- Keep changes scoped to the ticket. ASK before adding a dependency or changing the stack.
- Definition of Done (spec §13): AC demoed on a deployed env; any rule touched is enforced
  at the data layer with a rule-removal test; works on desktop + a mid-range phone; FR/EN;
  survives a slow connection; peer-reviewed; no secrets in the browser bundle.

## How to add a business rule (fill/confirm during FND-6)
1. Write a failing test at the data layer (not HTTP validation).
2. Enforce it in the model/DB/service so the test passes.
3. Add a bypass test (lowest-level insert path) proving it still can't be dodged.
4. Confirm "fails if removed": delete the rule → tests go red → restore → green.

## Common commands

### Local database (Postgres via Docker)
```bash
docker compose up -d        # starts Postgres on localhost:5435
docker compose down          # stop (data persists in the postgres_data volume)
```
Creates two databases on first boot: `concierge_ride` (app) and `concierge_ride_test`
(Pest's feature tests run against this one — see /docs/adr/0001-stack.md).

### Backend (`/backend`)
```bash
composer install              # install PHP dependencies
cp .env.example .env          # first-time only
php artisan key:generate      # first-time only
php artisan migrate           # run migrations
php artisan migrate:fresh     # drop all tables and re-migrate
php artisan db:seed           # run seeders
php artisan serve             # dev server -> http://localhost:8000
php artisan test              # run the Pest suite (equivalently: ./vendor/bin/pest)
./vendor/bin/pint             # format PHP (Laravel Pint)
```

### Frontend (`/frontend`)
```bash
npm install                   # install dependencies
cp .env.example .env          # first-time only — sets PUBLIC_API_URL
npm run dev                   # dev server -> http://localhost:5173
npm run build                 # production build
npm run test                  # run Vitest once
npm run test:unit             # run Vitest in watch mode
npm run check                 # svelte-check (types)
npm run lint                  # prettier --check + eslint
npm run format                # prettier --write
```

### End-to-end smoke check
1. `docker compose up -d`
2. `cd backend && php artisan serve`
3. `cd frontend && npm run dev` (separate shell)
4. Visit `http://localhost:5173` — should show "API health check: ok"
   (backed by `GET http://localhost:8000/api/health` → `{"status":"ok"}`)
