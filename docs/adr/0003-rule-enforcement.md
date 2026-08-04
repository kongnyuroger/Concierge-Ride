# 0003 — Testing Approach & Per-Rule Enforcement (NFR-6)

## Status
Accepted — 2026-08-04

## Context
NFR-6 requires every business rule (BR-*) to be enforced at the data layer, not just in
a form or the UI, and to ship with a test that fails if the rule is removed. We need a
standard testing stack and a repeatable procedure contributors follow for every rule.

## Options considered
1. **PHPUnit only, HTTP-level tests.** Simple, but HTTP tests can't prove a rule survives
   a direct model/DB bypass — exactly what NFR-6 guards against.
2. **Pest (backend) + Vitest (frontend), data-layer-first tests, with a fixed 4-step rule
   procedure.** More setup discipline, but directly enforces NFR-6's intent.
3. **Add Playwright now for E2E.** Valuable but not needed to satisfy NFR-6 itself;
   defer until there's real UI flow to cover.

## Decision
**Option 2.** Already implemented:
- Backend: **Pest**, installed and configured to run against a real **PostgreSQL**
  database (not SQLite) — SQLite doesn't enforce the same constraints Postgres does, and
  NFR-6 is about DB-layer guarantees, so the test DB has to be the real engine.
- Frontend: **Vitest**, installed and configured for unit tests. **Playwright** is in the
  stack list (CLAUDE.md) for later — not installed yet; no ticket has needed E2E coverage
  so far.
- CI (`backend-ci.yml`) runs Pest against a real `postgres:16` service container on every
  PR/push, so a rule that only "works locally" can't merge.

**The per-rule procedure (already in CLAUDE.md, restated here as the ADR of record):**
1. Write a failing test at the data layer (model/DB, not HTTP validation).
2. Enforce the rule in the model/DB constraint/service so the test passes.
3. Add a bypass test at the lowest-level insert path (e.g. direct `DB::table()->insert()`
   or `Model::withoutEvents()`) proving the rule still can't be dodged.
4. Confirm "fails if removed": comment out the enforcement → tests go red → restore →
   green. (Reviewers should spot-check this on rule-touching PRs, not just trust it.)

## Consequences
- **Cost:** none beyond normal dev time — Pest/Vitest are already installed.
- **Risk:** running Pest against real Postgres in CI is slower than SQLite-in-memory;
  acceptable trade for correctness given NFR-6's explicit intent.
- Every future PR touching a BR-* rule must include a bypass test; PR review should
  reject rule changes without one.
- Playwright stays a follow-up ADR-free addition (already pre-approved in the stack list)
  whenever a ticket first needs E2E coverage.
