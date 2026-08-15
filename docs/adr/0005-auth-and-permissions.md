# 0005 — Auth & Permission Model

## Status
Accepted — 2026-08-04. Amended 2026-08-13: CR-15 implemented enforcement via route
`permission:` middleware, not Policies as originally decided below — see ADR 0011 for why
and for the actual role/permission matrix. Sanctum and the "no customer login" points
below are unchanged.

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
- **spatie/laravel-permission installed in CR-15** — the first ticket that introduced
  protected resources, as planned.
- **Enforcement point, as actually built (CR-15): route `permission:` middleware**, not
  Policies. The areas gated so far (price book, audit log, accounts, leads/jobs/
  customers, the two queues) are stub routes with no model-level authorization logic yet
  — there's nothing for a Policy to attach to. Route middleware is still inspectable in
  one place (`routes/api.php`) and still satisfies "enforced where it can't be bypassed."
  Policies remain the right tool for future per-instance checks ("can this dispatcher
  edit *this* job") layered on top of the same permission-gated routes — this decision
  doesn't rule that out, it just wasn't needed for BR-16's role-level matrix. See ADR
  0011 for the full reasoning and the matrix itself.
- **Customers never authenticate** — reaffirming CLAUDE.md: there is no customer login
  surface to design at all. Every actor in the system is internal staff.

## Consequences
- **Cost:** none — Sanctum and spatie/laravel-permission are both free, MIT-licensed.
- Role list is now defined (owner, dispatcher, account-manager — BR-16, ADR 0011), tested
  per-role with a real HTTP request through the actual middleware, not a direct
  `Gate::allows()` call — see ADR 0011's test approach.
- Future authorization that needs *per-instance* checks (not just "does this role have
  this permission" but "does this specific user own this specific record") will need
  Policies after all, layered on top of the route middleware CR-15 established — not a
  contradiction of this ADR, just a layer this ticket didn't need yet.
