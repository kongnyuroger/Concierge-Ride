# 0003 — Rule Enforcement Pattern (NFR-6)

## Status
Accepted — 2026-08-04. Supersedes a speculative draft of this same ADR written in CR-5
(filed under a different filename, before any real rule existed) — CR-10 proved the
pattern against a real rule (BR-6) and this rewrite reflects what TDD actually showed.

## Context
NFR-6: every business rule (BR-*) must be enforced at the data layer, with a test that
fails if the rule is removed — never enforced only in a form or the UI. CR-10 built the
first real rule (BR-6: a job's pickup time must be in the future at creation) test-first,
specifically to prove this pattern rather than assert it in advance.

## Testing stack
- Backend: **Pest**, against a real **PostgreSQL** database (`concierge_ride_test`, not
  SQLite) — SQLite doesn't enforce the same constraints Postgres does, and NFR-6 is about
  Postgres-specific guarantees (`CHECK` immutability, trigger behavior), so the test DB
  has to be the real engine.
- CI (`backend-ci.yml`) runs Pest against a real `postgres:16` service container on every
  PR/push, so a rule that only "works locally" can't merge.
- Frontend: Vitest is installed; **Playwright** stays deferred (pre-approved in the stack
  list, ADR 0001) until a ticket actually needs E2E coverage — NFR-6 itself is a backend/
  data-layer concern.

## The pattern

**Where rules live — usually two layers, not one:**
1. A **model guard/observer** (e.g. `App\Observers\JobObserver`, registered via
   `Job::observe(...)` in `AppServiceProvider::boot()`) throwing
   `App\Exceptions\BusinessRuleException::violated('BR-N', '...')`. This guards normal
   Eloquent usage (`Model::create()`, `->save()`) with a clear, identifiable exception
   type — and is what NFR-6's "model guard/observer" wording refers to.
2. A **DB constraint** — a `CHECK` where the rule is expressible that way (same-row,
   immutable expression), otherwise a `BEFORE INSERT`/`BEFORE UPDATE` trigger (Postgres
   rejects `now()`/non-immutable functions in `CHECK`, so time-based or cross-table rules
   need a trigger, not a `CHECK`).

**Why both:** a model observer alone does not satisfy NFR-6. Eloquent events never fire
for `DB::table(...)->insert(...)` or raw SQL — so *any* rule enforced only by an observer
can be bypassed by going one layer lower, regardless of what the rule checks. CR-10's
bypass test proved this directly: with only `JobObserver` in place, `DB::table('jobs')
->insert([...'pickup_at' => past...])` succeeded silently. The DB constraint/trigger is
the layer that actually can't be dodged; the observer is a convenience layer on top of
it for nicer errors during normal application usage, not a substitute for it.

**Naming:**
- One test file per rule: `tests/Feature/BusinessRules/<RuleDescription>Test.php` (not
  named after the rule code alone — `PickupTimeMustBeFutureTest`, not `Br6Test` — so the
  test's purpose is obvious from its filename).
- Trigger functions: `enforce_<what_it_enforces>()` (e.g. `enforce_pickup_in_future`),
  distinct from BR-11's cross-cutting `prevent_hard_delete()` (one shared function reused
  across every table, since that rule is identical everywhere — most rules won't be
  cross-cutting like that and get their own function).
- Exceptions: always `BusinessRuleException::violated('BR-N', 'human message')` — never a
  bare `RuntimeException` or a `ValidationException` (that's for HTTP-layer form
  validation, a different concern from a domain invariant).

## How rules are tested
Two tests minimum, in the same file:
1. **The rule, via the model layer** — `Model::create([...])` with a violating value,
   asserting the specific `BusinessRuleException` (or a DB-level exception if the rule is
   trigger-only with no observer). Write this FIRST, red, and confirm it's red because
   *nothing rejects it yet* — not for an unrelated reason (missing class, wrong table,
   etc.) — before writing any enforcement.
2. **The bypass test** — the same violation via `DB::table(...)->insert(...)` (or raw
   SQL), asserting it's *still* rejected. If the rule only has a model guard so far,
   expect this test to legitimately fail — that failure is the signal to add a DB
   constraint/trigger, not a sign something's wrong with the test.

## The "fails if removed" loop (demonstrated on BR-6, not just claimed)
1. Comment out the enforcement (the trigger function's `RAISE EXCEPTION` block, or the
   observer's registration in `AppServiceProvider`) — actually edit the file. Manually
   dropping a live DB trigger via `psql` does **not** work as a substitute: Pest's
   `RefreshDatabase` runs `migrate:fresh` from the migration *files* at the start of each
   test process, silently undoing any out-of-band DB edit. The rule only "isn't there"
   from the test suite's point of view if the migration file itself doesn't create it.
2. Run the test(s) — confirm red.
3. Restore the file, confirm green again.

**A real nuance CR-10 hit, worth expecting:** with both layers in place, disabling *only*
the observer still left the bypass-style rejection working (the trigger caught it) — but
the model-layer test still correctly went red, because it got a `QueryException` instead
of the expected `BusinessRuleException`. That's a feature, not a bug: the test is coupled
to which layer is actually catching the violation, so an accidental regression in either
layer shows up in CI even though the *data* stayed safe either way (defense in depth).

## Reference implementation
`backend/tests/Feature/BusinessRules/PickupTimeMustBeFutureTest.php`,
`backend/app/Observers/JobObserver.php`, `backend/app/Exceptions/BusinessRuleException.php`,
`backend/database/migrations/2026_08_04_212923_add_pickup_time_guard_to_jobs_table.php`
(CR-10). Copy this shape for every future BR-*.

## Consequences
- **Cost:** one extra file (observer) plus one extra migration per rule that needs a
  trigger, versus a single mechanism — the price of the bypass test actually meaning
  something.
- **Risk:** a rule expressible as a plain `CHECK` (same-row, immutable — e.g. BR-3) needs
  no observer or trigger at all, just the constraint; don't add a redundant observer for
  those. This two-layer shape is specifically for rules a `CHECK` can't express.
- PR review for any rule-touching change should ask for the bypass test and the
  fails-if-removed evidence, not just trust the enforcement code looks right.
