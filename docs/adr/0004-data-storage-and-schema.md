# 0004 — Data Storage & Schema Approach

## Status
Accepted (engine + conventions) — 2026-08-04
Money representation: **PROPOSED — pending owner approval**

## Context
We need a database engine (already fixed — see ADR 0001) plus a set of schema
conventions that make the money/history-integrity rules in CLAUDE.md ("prices come only
from the price book," "a job captures price + payout as stored values," "records are
never hard-deleted") mechanically easy to follow correctly, not just documented.

## Options considered
**Engine:** already decided in ADR 0001 (PostgreSQL). Not reopened here.

**Soft-delete strategy:**
1. Laravel's `SoftDeletes` trait (adds `deleted_at`, hides rows from default queries).
2. An explicit `status`/`state` column per table (e.g. `active`, `cancelled`,
   `superseded`), driven by spatie/laravel-model-states.

**Money representation:**
1. Decimal column (e.g. `decimal(10,2)`).
2. Integer minor-unit column (store cents/smallest unit).
3. Integer whole-unit column — XAF (the local currency) has no minor subunit in
   everyday use, so "cents" don't apply; store whole francs as an integer.

## Decision
- **Soft-delete strategy: option 2 (status/state column).** Matches CLAUDE.md's explicit
  rule directly and pairs with the already-adopted `spatie/laravel-model-states` state
  machine for job lifecycle (ADR 0001) — one mechanism instead of two competing ones
  (Eloquent's `SoftDeletes` vs. an explicit state machine) touching the same row.
- **Captured-value convention:** any table recording a business transaction (a job's
  price, a driver's payout) stores those as columns on that row at creation time —
  never a live foreign-key lookup into the price book or a rate table. This is already
  policy per CLAUDE.md; this ADR just names it as a schema rule, not only a process rule.
- **Migrations remain the schema source of truth** (Laravel migrations, already the
  pattern in `backend/database/migrations`); no separate schema-definition tool.

**Money representation — PROPOSED:** recommend **option 3 (integer whole XAF)**. XAF is
not typically subdivided in Cameroon commerce, so an integer column avoids float/decimal
rounding entirely and keeps arithmetic in application code trivial. This needs the
project owner's sign-off before the first migration that adds a money column (likely the
price book / job tables), since it's a one-way door — changing representation later
means a data migration across every historical row.

## Consequences
- **Cost:** none — no new dependency; `spatie/laravel-model-states` already in the stack.
- **Risk:** if a future requirement needs sub-unit precision (e.g. a partner integration
  quoting in USD/EUR cents), the integer-XAF convention needs an explicit per-column
  exception, not a silent float. Flag this in the price-book/job schema ticket.
- No domain tables exist yet (only Laravel's default `users`/`cache`/`jobs`/
  `personal_access_tokens`); this ADR governs the first real schema ticket, it doesn't
  retrofit anything.
