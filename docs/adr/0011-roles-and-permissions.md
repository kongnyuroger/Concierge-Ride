# 0011 — Roles & Permissions (BR-16)

## Status
Accepted — 2026-08-13

## Context
CR-7 built authentication; nothing enforced *what* an authenticated user could do. BR-16
defines three roles — owner, dispatcher, account-manager — and this ticket (CR-15/M1-3)
had to turn that into a real, tested permission layer before the features it gates
(price book CR-13, audit log CR-32, account creation CR-20, leads/jobs CR-11/12, company
accounts CR-20) exist. Many later tickets depend on this being correct, not quick.

## The matrix (derived from BR-16, approved before implementation)
> "Dispatchers may create and operate leads and jobs, and add customers. They may not
> alter the price book, driver agreed rates, user accounts, or view the audit log.
> Account managers additionally manage company accounts. Only the owner may do the rest."

| Area | Permission | Owner | Dispatcher | Account-manager |
|---|---|:---:|:---:|:---:|
| Leads | `leads.manage` | ✅ | ✅ | ✅ |
| Jobs (board, matching, assignment, lifecycle) | `jobs.manage` | ✅ | ✅ | ✅ |
| Customers | `customers.manage` | ✅ | ✅ | ✅ |
| Individual/dispatcher inbox queue | `queue.individual.view` | ✅ | ✅ | ✅ |
| Company/corporate inbox queue | `queue.company.view` | ✅ | ❌ | ✅ |
| Price book | `price-book.manage` | ✅ | ❌ | ❌ |
| Driver agreed rates | `driver-rates.manage` | ✅ | ❌ | ❌ |
| User accounts | `accounts.manage` | ✅ | ❌ | ❌ |
| Audit log | `audit-log.view` | ✅ | ❌ | ❌ |

Three judgment calls made explicit before coding, not assumed:
1. **Account-manager = dispatcher's base set + the company queue**, not a disjoint role.
   BR-16 says account-managers "*additionally* manage company accounts" — additionally,
   on top of what dispatchers already have.
2. **Price book stays one owner-only permission**, not split into view/manage. BR-16 only
   forbids dispatchers from *altering* it, which arguably implies read access — but
   designing that split is CR-13's job (the real price-book feature), not this ticket's.
3. **Owner bypasses every check rather than having all permissions explicitly assigned**
   — see "Owner implementation" below.

## Decision

### Enforcement: route middleware, not policies
Every protected area is a `Route::middleware("permission:{name}")` group in
`routes/api.php`, using spatie/laravel-permission's built-in `PermissionMiddleware`
(aliased as `permission` in `bootstrap/app.php`). No Policy classes — none of these areas
have per-instance authorization logic yet (that's "can this dispatcher edit *this* job",
which isn't what BR-16 asks for). A future ticket needing that layers a Policy on top of
the same permission-gated route; this ADR doesn't preclude it.

### Owner implementation: `Gate::before`, not assigned permissions
```php
Gate::before(fn (User $user, string $ability) => $user->hasRole(UserRole::Owner->value) ? true : null);
```
in `AppServiceProvider::boot()`. The alternative — assigning all 9 permissions to the
owner role in the seeder — requires remembering to update the seeder every time a future
ticket adds a permission. The bypass means the owner automatically covers anything added
later with zero seeder changes. Trade-off: the owner's access is less visible in the
`role_has_permissions` table than an explicit assignment would be — worth knowing if
someone's debugging by reading that table directly instead of testing behavior.

Because permissions are stored only for dispatcher/account-manager, `GET /api/user`
would report an **empty** permissions array for the owner if it just read
`getAllPermissions()`. `User::permissionNames()` special-cases the owner to return the
full `Permission` enum list instead — otherwise the frontend's nav-gating would hide
everything from the one role that should see everything.

### Permission and role names: PHP enums, not raw strings
`App\Enums\Permission` and `App\Enums\UserRole` are the single source of truth for every
string used in the seeder, routes, and tests — typo-proof, autocompletable. spatie's own
tables still store plain strings (`$permission->value`); the enum is a compile-time
safety net around that, not a schema change.

### Stub routes: real permission gates on routes with no real feature yet
Nine thin stub routes (`leads`, `jobs`, `customers`, `queues/individual`,
`queues/company`, `price-book`, `driver-rates`, `accounts`, `audit-log`) exist purely so
the matrix is testable end-to-end today — including three areas (leads, jobs, customers)
the ticket didn't name explicitly but BR-16's text does, and that have no routes at all
yet from CR-9 (which only built migrations/models). Built as one loop over an
`area => permission` array in `routes/api.php`, not nine near-duplicate closures. When
CR-11/12/13/20/32 build the real feature, they replace one array entry's closure with a
real controller — the path, route name, and permission stay put.

### Frontend: UX only, explicitly labeled as such
`lib/permissions.ts`'s `can()`/`visibleNavItems()` hide nav items a role can't use. The
file's top comment says outright that this enforces nothing — a disallowed request
against the API works identically whether or not this file exists. Nav hrefs point at
routes that don't exist as pages yet (same future-tickets-attach-here pattern as the
backend stubs); `resolve()` isn't used for them since it only accepts routes that already
exist, so plain `href` strings are used with an eslint-disable for
`svelte/no-navigation-without-resolve` on that block.

## Two real bugs found building this (both fixed, both worth knowing)
- **`WithoutModelEvents` + spatie's permission cache don't mix.** `DatabaseSeeder` uses
  Laravel's `WithoutModelEvents` trait, which suppresses the model `created`/`saved`
  events spatie's cache relies on to invalidate itself. Result: `Role::syncPermissions()`
  failed claiming a permission didn't exist, immediately after that exact permission was
  created in the same seeder run — the DB was correct, the cache was stale. Fixed with an
  explicit `PermissionRegistrar::forgetCachedPermissions()` call in `PermissionsSeeder`
  after creating permissions. Documented spatie gotcha, not obvious from the error message.
- **`Permission::findOrCreate()` / `Role::findOrCreate()` guard resolution** — not
  actually a bug (both consistently resolved to the `web` guard, verified by querying the
  DB directly during debugging), but easy to suspect first given the error message
  ("no permission named X for guard web") reads exactly like a guard mismatch. Worth
  ruling out the cache before chasing guard configuration if this resurfaces.

## Consequences
- Every future ticket that adds a permission: add it to the `Permission` enum, decide
  which role(s) get it in `PermissionsSeeder::ROLE_PERMISSIONS`, done — the owner
  automatically gets it via the bypass, no seeder change needed for that part.
- The stub routes are dead weight once their real controller lands — deleting the array
  entry (not just replacing the closure) is fine once CR-11/12/13/20/32 fully own that
  route.
- `lib/permissions.ts`'s permission-name strings have no shared source with the backend
  enum — if `Permission.php` changes, someone has to remember to update the TypeScript
  list by hand. A codegen step could close this gap later; not worth it for nine strings
  today.
- Tests seed via the real `PermissionsSeeder` (`$this->seed(PermissionsSeeder::class)` in
  `beforeEach`), not a duplicated in-test role/permission definition — so the tests prove
  the actual seeder produces the actual matrix, not just that the matrix *would* work if
  someone set it up by hand correctly.
