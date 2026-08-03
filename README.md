# Concierge Ride

Internal dispatch cockpit for a chauffeur / ground-transport business in Cameroon (Douala &
Yaoundé). Monorepo containing the API and the dispatcher-facing web app.

See [CLAUDE.md](./CLAUDE.md) for the project guide (domain, stack, conventions, and the
non-negotiable rule for enforcing business rules at the data layer).

## Layout

- `/backend` — Laravel API (PHP, PostgreSQL, JSON only — no Blade UI)
- `/frontend` — SvelteKit app (TypeScript, Tailwind) that consumes the API
- `/docs` — build spec
- `/docs/adr` — architecture decision records

## Quickstart

See the "Common commands" section of [CLAUDE.md](./CLAUDE.md) for the full list. Short
version:

```bash
# 1. Start Postgres
docker compose up -d

# 2. Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve

# 3. Frontend (separate shell)
cd frontend
cp .env.example .env
npm install
npm run dev
```

Backend health check: `GET http://localhost:8000/api/health` → `{"status":"ok"}`
Frontend: `http://localhost:5173` — displays the API health status.
