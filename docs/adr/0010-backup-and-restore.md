# 0010 — Backup & Restore (NFR-8)

## Status
**PROPOSED — pending owner approval**

## Context
NFR-8 requires a backup/restore capability. ADR 0002 provisioned staging on Render's
**free** Postgres plan, which has no backup guarantee at all and auto-expires 30 days
after creation — it was an explicit, flagged gap in that ADR, not an oversight, but it
still needs a real answer before production exists (ADR 0002 also deferred provisioning
production for the same cost reasons).

## Options considered
1. **Render's built-in Postgres backups** (available on paid plans — daily backups with
   a retention window; point-in-time recovery on higher tiers). No new vendor, same
   dashboard/CLI already in use.
2. **Application-level scheduled export** (e.g. a Laravel scheduled command running
   `pg_dump` to an object store like S3/Backblaze). More engineering to build and
   maintain, but portable if hosting ever changes and gives full control over retention.
3. **No formal backup, rely on Render's platform durability only.** Free, but doesn't
   satisfy NFR-8 at all — data loss from a bad migration, accidental delete, or platform
   incident would be unrecoverable. Not a real option given NFR-8 exists.

## Recommendation
**Option 1 for production, deferred until production Postgres is actually provisioned**
(per ADR 0002, that's a paid-plan decision already pending). Render's managed backups are
the lowest-effort way to satisfy NFR-8 and come "for free" with the paid Postgres plan
ADR 0002 already recommends for production — no separate cost line, no separate system to
build/monitor. Revisit option 2 only if/when the project needs to be portable off Render,
since a `pg_dump`-based pipeline is genuinely more work to build correctly (encryption at
rest, tested restores, off-platform storage) than it looks.

**Staging** doesn't need durable backups (it's disposable/reproducible from migrations +
seeders), but its current free-tier **30-day hard expiry** (flagged in ADR 0002) is a
real near-term risk, not a backup gap — track that separately, it isn't NFR-8's concern.

## Consequences (if approved as recommended)
- **Cost:** no separate line item — bundled into the production Postgres paid plan ADR
  0002 already prices at ~$6–7/mo (Basic 256mb tier includes daily backups).
- **Risk:** a "restore" has never actually been tested, because there's no production
  database yet. The first production-readiness ticket should include a real restore
  drill (restore a backup to a scratch instance, verify data), not just trust the
  dashboard button exists.
- **Risk:** retention window on Render's Basic tier is limited (days, not months) — if
  the business needs longer retention for compliance/dispute-resolution reasons, that's
  a plan-tier decision to revisit explicitly, not assumed.
- No backup/restore procedure is documented yet beyond this ADR's recommendation; the
  ticket that provisions production Postgres should also write the actual restore
  runbook.
