# 0012 — Price Book (BR-1, BR-2)

## Status
Accepted — 2026-08-15

## Context
CR-9 created a `price_book_entries` table keyed by `(product_id, tier_id)` with a single
`price` column — a placeholder shape, not §8's real one. This ticket (CR-13/M1-1) is the
spine of the pricing lane: CR-14 (real seed data), CR-16 (job creation/pricing), and CR-17
(override/margin-approval) all build on whatever shape and API this ticket ships. Getting
the data shape and the edit semantics right matters more than a working screen.

BR-1 and BR-2 (pasted verbatim by the project owner, not paraphrased from a draft spec):
> BR-1: A job's customer price is derived from the price book by city + product + tier. It
> is never free-typed.
> BR-2: A price differing from the price book requires a recorded reason. If the resulting
> margin falls below the configured margin floor, the job is flagged for owner approval
> and cannot be completed until approved.

## The entry shape (derived from BR-1/BR-2 + CR-9's schema, approved before implementation)
A price-book entry is keyed by **city × product × tier** — BR-1 names city explicitly,
which CR-9's `(product_id, tier_id)` key was missing entirely, so this ticket adds a new
`cities` table (mirrors the existing `products`/`tiers` pattern) and a `city_id` FK rather
than inferring city from anything else.

| Field | Type | Notes |
|---|---|---|
| `city_id`, `product_id`, `tier_id` | FK | the key; unique together while `status = 'active'` |
| `customer_price` | integer, XAF | renamed from CR-9's `price` — BR-2 needs to talk about "the price" and "the driver payout" unambiguously, `price` alone was already ambiguous once payouts entered the picture |
| `included_hours` / `overage_rate_per_hour` | integer, nullable, paired | for time-boxed products (half/full-day). Both null or both set — never one without the other |
| `included_distance_km` / `overage_rate_per_km` | integer, nullable, paired | same pairing rule, for distance-boxed products |
| `margin_floor` | integer, XAF, required | the minimum margin an override (CR-17) may not cross — BR-2's threshold |
| `status` | `active` \| `superseded` | versioning, see below — never hard-deleted (CLAUDE.md) |

Four judgment calls made explicit before coding, not assumed:
1. **Capacity is NOT a price-book field.** It's already `tiers.passenger_capacity` — a
   fixed property of the tier, not something that varies by city/product. Duplicating it
   onto every entry would let city/product-specific capacity drift from the tier's real
   capacity for no reason BR-1/BR-2 asks for.
2. **Both allowance pairs are nullable independently** — a product can be flat-rate (both
   pairs null, e.g. airport transfer), hour-boxed only (half/full-day today), km-boxed
   only, or in principle both. Enforced as two independent pairs, not a single "has an
   allowance" flag.
3. **`margin_floor` is a flat XAF amount, not a percentage.** BR-2 says "falls below the
   configured margin floor" — read as an absolute amount to compare against a computed
   margin (customer_price − driver_payout), not a percentage requiring a second
   derivation step. CR-17 (the ticket that actually evaluates it) can revisit if wrong.
4. **Premium's price-book rows exist but are unreachable via a real job** — BR-4 (Premium
   never bookable) is enforced at the tier level (`tiers.is_bookable`), not by omitting
   Premium from the price book. Keeping the row is consistent with "the price book prices
   everything the catalog defines" and costs nothing since CR-16 will never let a job
   reference a non-bookable tier regardless.

## Decision

### Editing preserves history: supersede + insert, never UPDATE
`PriceBookEntry::createNewVersion()` is the only sanctioned way to change an entry's
values. Inside a transaction: mark the current `active` row for the `(city, product,
tier)` key as `superseded`, then insert a brand-new `active` row with the new values.
Never an `UPDATE` on an existing row's money/allowance fields.

This is what makes the money & history integrity rule (CLAUDE.md) hold without any
special-casing on the job side: a job's `price_book_entry_id` FK points at one specific,
now-immutable row. Editing the "current" price for a key only ever changes which row is
`active` — it can't retroactively change what an existing job's FK points at, or what
values that row holds. CR-16 (when it exists) just needs to resolve `(city, product,
tier) → active entry` at job-creation time and copy `customer_price` into the job's own
stored `price` column; nothing here has to know CR-16 exists yet for the guarantee to
hold, which is exactly the ticket's "must only affect NEW jobs" boundary.

Trade-off: the table accumulates superseded rows forever (no cleanup path) — acceptable,
matches the same "never hard-delete" trade-off CLAUDE.md already makes everywhere else.

### Enforcement: same `permission:` middleware CR-15 already established
`price-book.manage` (ADR 0011) gates `GET /price-book` and `PUT /price-book/{id}` — no new
enforcement mechanism, just the real controller replacing CR-15's stub closure at the
route CR-15 had already reserved. Owner passes via the existing `Gate::before` bypass;
dispatcher/account-manager get a 403 from the same middleware every other BR-16 area uses.

### Pairing enforced at both the request-validation layer and the DB layer
`included_hours`/`overage_rate_per_hour` (and the km equivalent) must be set or unset
together. Enforced twice, independently: `required_with` in both directions in the
controller's validation (a clean 422 for the owner editing through the UI), and a Postgres
`CHECK ((included_hours IS NULL) = (overage_rate_per_hour IS NULL))` (the layer that can't
be bypassed by inserting through anything other than the controller) — same two-layer
shape as every other BR-* rule per ADR 0003, even though this one isn't itself a numbered
BR — it's the schema-level expression of "an allowance pair means something as a pair."

### Frontend: plain table, inline edit, no client-side permission re-check
The `/price-book` page assumes CR-15's nav gating already hides the link from anyone
without `price-book.manage`; it does not duplicate a permission check client-side. A
dispatcher who types the URL directly still gets a real 403 from the API (the actual
enforcement boundary), which the page just renders as a load error — it doesn't add a
second, redundant authorization decision in JavaScript. Editing a row calls `PUT`, then
refetches the full list rather than patching the edited row in place, because a successful
edit's response is a **different row with a different id** (the old one superseded) — the
old id has nothing left to patch.

## Consequences
- CR-14 (real §8 seed data) replaces `ReferenceDataSeeder`'s placeholder price grid and
  hour allowances; the shape this ticket built doesn't need to change for that.
- CR-16 (job creation) resolves `(city, product, tier) → active entry` and copies
  `customer_price` (and, later, `driver_payout` from a separate driver-rates concern) into
  the job's own stored columns at creation. This ADR's guarantee — editing an entry never
  touches an existing job — holds regardless of how CR-16 does that resolution.
- CR-17 (override/margin-approval) reads `margin_floor` off the entry a job was priced
  against to decide whether an override needs owner approval — this ticket doesn't
  implement that comparison, just makes the field available.
- `price_book_entries` grows without bound (every edit adds a row, nothing is ever
  deleted). Fine at this business's scale; worth revisiting only if it ever isn't.
