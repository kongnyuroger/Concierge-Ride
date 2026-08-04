# 0005 — Auth & Permission Model

## Status
Accepted — 2026-08-04

## Context
Concierge Ride has no customer-facing login — only internal staff (dispatchers, and
likely admins/ops roles later) authenticate. We need a session/token mechanism and a way
to express "who can do what" that's enforced server-side, not just hidden in the UI.

## Options considered
1. **Laravel Sanctum (SPA/token auth) + spatie/laravel-permission for roles**, policies
   enforced in controllers/form requests.
2. **Laravel Passport (full OAuth2 server).** Overkill — no third-party API consumers,
   no need for OAuth grants/scopes; adds real operational complexity (key rotation, token
   introspection) for no benefit here.
3. **Hand-rolled roles column + manual `if` checks in controllers.** Fast to start, but
   drifts fast and is exactly the kind of "enforced only in a form" pattern NFR-6 exists
   to prevent — permission checks would live in scattered controller code instead of one
   inspectable place.

## Decision
**Option 1.** Already fixed in the stack (ADR 0001) and partially implemented:
- **Sanctum is installed** (`laravel/sanctum`, `personal_access_tokens` migration
  present) — SPA-style cookie/token auth for the SvelteKit frontend talking to the
  Laravel API, same-origin-friendly, no OAuth ceremony.
- **spatie/laravel-permission is decided but not yet installed** — no roles/permissions
  exist yet because no domain models/routes need them yet. Add it in the first ticket
  that introduces a protected resource.
- **Enforcement point: Laravel Policies**, registered per model, checked via
  `$this->authorize()` in controllers/form requests — not ad hoc `if ($user->role ===
  ...)` checks scattered through the codebase. Policies are unit-testable in isolation,
  which also satisfies NFR-6-style "enforced where it can't be bypassed" thinking for
  authorization specifically (though NFR-6 itself is about business rules, not auth).
- **Customers never authenticate** — reaffirming CLAUDE.md: there is no customer login
  surface to design at all. Every actor in the system is internal staff.

## Consequences
- **Cost:** none — Sanctum and spatie/laravel-permission are both free, MIT-licensed.
- **Risk:** until spatie/laravel-permission is actually installed, there are no roles to
  misconfigure — low risk now, but the first ticket that adds it should also add a
  bypass-style test (direct policy check, not just an HTTP 403 test) per ADR 0003's
  pattern, since permission checks are exactly the kind of thing that looks enforced in
  a controller but isn't enforced if called from a queued job or console command.
- Role list itself (dispatcher, admin, etc.) is not yet defined — first permissions
  ticket should enumerate roles against the actual product spec, not invent them here.
