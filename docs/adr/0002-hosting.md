# 0002 — CI and Hosting

## Status
Accepted — 2026-08-04

## Context
The monorepo skeleton (ADR 0001) ran locally but wasn't reachable at a URL and had no
CI gate. CR-6/FND-2 asks for: GitHub Actions running backend + frontend checks on every
PR and push, a real deployment with staging and production environments, the frontend's
API base URL wired per environment, and a documented deploy path with cost and licensing
confirmed.

The repo's actual default branch is `dev` (no `main` exists), so "push to main" in the
ticket means push to `dev` throughout this document.

## Decision: Render (API + Postgres) + Vercel (frontend)
Originally scoped as a single-platform choice (Railway, Render, or Fly.io — see the
"Alternatives considered" section). Mid-implementation we switched to a two-platform
split: **Render** for the Laravel API and Postgres, **Vercel** for the SvelteKit
frontend.

- **Vercel is SvelteKit's native host.** `@sveltejs/adapter-vercel` is zero-config, and
  every push gets an automatic Preview deployment with its own URL — a closer match to
  "PR → reachable URL" than any generic host.
- **Render runs arbitrary Docker containers with a managed Postgres**, which is what the
  Laravel side needs. Fixed, predictable per-service pricing.
- **Trade-off vs. one platform:** two dashboards instead of one, and no shared
  "environment" abstraction spanning both services — staging and production are separate
  Render services / Vercel deployment targets, wired by hand rather than cloned together.

## Deploy mechanics (what's actually live)

### Backend — Render
Render has no native PHP buildpack (unlike Railway's Nixpacks), so the API ships as a
Docker image. Laravel's officially recommended container runtime is
[FrankenPHP](https://frankenphp.dev) — see `backend/Dockerfile` and
`backend/docker/entrypoint.sh`.

Two real issues surfaced deploying to Render specifically, both fixed in the Dockerfile
and worth knowing if migrating hosts later:
1. **`frankenphp: Operation not permitted`** — the `dunglas/frankenphp` image sets
   `cap_net_bind_service` on the binary via `setcap` so it can bind port 80 as non-root.
   Render's sandboxed runtime rejects `exec` of a binary carrying that capability. We
   bind a high port via `$PORT` anyway, so the Dockerfile strips the capability at build
   time (`setcap -r`).
2. **Pre-deploy commands require a paid Render plan** — the free plan silently skips
   them, so `php artisan migrate --force` never ran as a pre-deploy step. Migration runs
   from `entrypoint.sh` on every boot instead (idempotent; safe while `numInstances=1`
   — revisit before scaling past one instance, to avoid concurrent migration races).

Render also needs its GitHub App authorized against the repo (Account → Connected
Accounts) to fetch live commits — without it, it silently builds a stale cached
snapshot instead of erroring. One-time, already done for this account.

- Staging service: `concierge-ride-api-staging`, root dir `backend`, Docker runtime,
  tracks `dev`, auto-deploys on push. Live at
  `https://concierge-ride-api-staging.onrender.com`.
- Staging Postgres: `concierge-ride-db-staging`, free plan, region `oregon`.
- **Production: not provisioned yet — deliberately.** Render allows only one free-tier
  Postgres per account, and a second (production) instance needs a paid Basic plan
  (~$6–7/mo). Rather than spend against an account with no real launch date yet, we
  documented the exact steps below and left it for whoever cuts the first real release.

**To stand up production when ready:**
```bash
render postgres create --name concierge-ride-db-production --plan basic_256mb --region oregon --version 16
render services create --name concierge-ride-api-production --type web_service --runtime docker \
  --repo https://github.com/kongnyuroger/Concierge-Ride --branch <release-branch-or-tag> \
  --root-directory backend --plan starter --region oregon --health-check-path /api/health \
  --env-var APP_ENV=production --env-var APP_DEBUG=false --env-var APP_KEY=<fresh key> \
  --env-var DB_CONNECTION=pgsql --env-var DB_HOST=<from postgres get> ...
```
Generate a **fresh** `APP_KEY` (`php artisan key:generate --show`) — never reuse
staging's. Auto-deploy should stay off (or point at a `production` branch/tag you push
to deliberately) so production only moves when someone intends it to.

### Frontend — Vercel
`@sveltejs/adapter-auto` (the `sv create` default) has no zero-config target for
Vercel/Render/Fly; swapped to `@sveltejs/adapter-vercel`.

Project: `concierge-ride-frontend`, Root Directory `frontend` (monorepo — set explicitly,
since Vercel's zero-config detection assumes repo root), Git-connected to
`kongnyuroger/Concierge-Ride`. Vercel's own "Production" tier tracks the repo's default
branch (`dev`) — in our terms that's **staging**, not a real production release; there is
no separate production Vercel deployment yet, matching the backend/DB decision above.
Live at `https://concierge-ride-frontend.vercel.app`.

`PUBLIC_API_URL` is set as a Vercel project env var (Production + Preview + Development,
all pointing at the staging backend for now) — `src/lib/api.ts` reads it via
`$env/static/public`, which resolves at **build time**, not runtime. This matters:
changing `PUBLIC_API_URL` requires a rebuild to take effect, and CI's typecheck step
needs *some* value present even though it never calls a real backend — see
`frontend-ci.yml`'s `PUBLIC_API_URL: http://localhost:8000` (not a secret; it's a public
URL by the `PUBLIC_` naming convention itself).

**Known gap — Vercel plan compliance:** the project is currently on Vercel's free
**Hobby** plan, which is a **decision, not an oversight**: Vercel's ToS restricts Hobby
to non-commercial use, defined broadly enough that any project someone is paid to build
or host counts as commercial — a dispatch business like Concierge Ride would not qualify,
strictly read. We're leaving it on Hobby through the walking-skeleton stage (no real
customer traffic yet) and flagging it here explicitly: **upgrade to Vercel Pro
(~$20/mo/member) before any real launch.**

## CI (GitHub Actions)
`.github/workflows/backend-ci.yml` and `frontend-ci.yml`, triggered on `pull_request`
and `push` to `dev`, path-filtered so a frontend-only change doesn't run the PHP job and
vice versa.
- Backend: PHP 8.3 + a real `postgres:16` service container → `composer install` →
  `migrate` → `./vendor/bin/pest --ci` → `./vendor/bin/pint --test`.
- Frontend: Node 20 → `npm ci` → `npm run check` → `npm run lint` → `npm run test`.

Two bugs only a real CI run caught (both fixed, both worth knowing about for future
work):
- `tests/Unit/` was empty after the bootstrap session deleted its example test — git
  doesn't track empty directories, so the folder silently never made it into the repo.
  Pest's `--ci` mode hard-fails when a configured testsuite directory doesn't exist
  (unlike a plain local run, which didn't notice because a leftover local directory
  papered over it). Fixed with a tracked `.gitkeep`.
- `backend/composer.lock` had resolved against the local machine's PHP 8.5, silently
  pulling in Symfony 8.x (requires PHP ≥8.4) — quietly breaking the declared
  `"php": "^8.3"` floor for CI, Docker, or any contributor actually on 8.3. Fixed by
  pinning `config.platform.php` to `8.3.99` in `composer.json` and regenerating the lock.

## Environment variables

**Backend (Render), staging:**
| Var | Value |
|---|---|
| `APP_NAME` | `Concierge Ride` |
| `APP_ENV` | `staging` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | generated, staging-only — never shared with production |
| `APP_LOCALE` | `en` |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | from `render postgres get concierge-ride-db-staging --include-sensitive-connection-info` |

**Frontend (Vercel), all targets:**
| Var | Value |
|---|---|
| `PUBLIC_API_URL` | `https://concierge-ride-api-staging.onrender.com` |

**CI only (not a real secret):**
| Var | Value | Where |
|---|---|---|
| `PUBLIC_API_URL` | `http://localhost:8000` | `frontend-ci.yml`, so `svelte-check` can resolve `$env/static/public` |

No secrets are committed anywhere; `.env`/`.env.local` stay gitignored in both apps.

## Cost estimate (current, staging-only)
| Item | Plan | Cost |
|---|---|---|
| Render Postgres (staging) | Free | $0/mo (⚠️ auto-deletes 30 days after creation — created 2026-08-04, expires 2026-09-03; must upgrade or recreate before then) |
| Render web service (staging) | Free | $0/mo (spins down after 15 min idle; ~30–60s cold start on the next request — acceptable for staging, not for production) |
| Vercel (frontend) | Hobby | $0/mo (⚠️ non-commercial only — see gap above) |
| **Current total** | | **$0/mo** |

**Once production is stood up** (per the steps above): Render Postgres Basic 256mb
(~$6–7/mo) + Render web service Starter (~$7/mo) + Vercel Pro (~$20/mo/member) ≈
**$35–40/mo**, plus staging's now-negligible free-tier cost. Cheap enough to defer until
there's an actual launch date, which is why we deferred it.

## Open-source / commercial-use check
| Component | License | Commercially usable? |
|---|---|---|
| Laravel | MIT | Yes |
| Laravel Sanctum | MIT | Yes |
| Pest | MIT | Yes |
| PostgreSQL | PostgreSQL License (permissive, OSI-approved) | Yes |
| SvelteKit / Svelte | MIT | Yes |
| Vite | MIT | Yes |
| Tailwind CSS | MIT | Yes |
| FrankenPHP | MIT | Yes |
| Render | Paid SaaS (not OSS — expected for a hosting vendor) | Yes, standard commercial ToS |
| Vercel | Paid SaaS (not OSS) | **Only on Pro or above** — Hobby explicitly excludes commercial use (see gap above) |

Every library in the stack is MIT or an equivalent permissive OSS license with no
copyleft or commercial-use restriction. The one real compliance item is Vercel's Hobby
plan, called out above and requiring action before a real launch — everything else is
clear.

## Alternatives considered
**Railway** was the original single-platform pick (native "Environments" abstraction,
Nixpacks auto-detects Laravel + SvelteKit, GitHub-gated deploys) — see the earlier
planning discussion. Switched to Render + Vercel mid-implementation because Vercel is
SvelteKit's native host with better preview-deployment ergonomics than Railway offers for
a frontend specifically, and Render's fixed per-service pricing was judged more
predictable for budgeting than Railway's usage-metered billing. **Fly.io** was ruled out
early: no built-in environment/preview concept, meaning staging + production would need
to be hand-built from separate apps plus custom CI glue, for no clear benefit over the
other two options at this project's scale.

## Consequences
- Production (Postgres, Render API service, and a real Vercel Pro deployment) is
  documented but **not live**. The next person to cut a real release needs to follow the
  "To stand up production" steps above, upgrade Vercel to Pro, and budget the ~$35–40/mo.
- Staging's free-tier Postgres expires 2026-09-03 unless upgraded or recreated first —
  worth a calendar reminder.
- Render's free web service cold-starts (~30–60s) are fine for a staging/demo URL but
  would need a paid plan before anyone treats staging as reliably fast.
- `PUBLIC_API_URL` is a build-time value (`$env/static/public`) — pointing the frontend
  at a different backend always requires a rebuild + redeploy, not just an env var flip.
