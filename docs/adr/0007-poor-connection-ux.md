# 0007 — Poor-Connection / Offline UX Strategy (NFR-1)

## Status
**PROPOSED — pending owner approval**

## Context
NFR-1 requires the app to "survive a slow connection" (per CLAUDE.md's Definition of
Done). Dispatchers in Douala/Yaoundé may work on patchy mobile data. Nothing has been
built for this yet — no service worker, no retry/queue logic, no offline indicator.

## Options considered
1. **Do nothing special — rely on browser defaults.** Free, but a lost request during a
   job update can silently fail or duplicate on manual retry; not a real answer to NFR-1.
2. **Resilient network layer only:** timeouts + automatic retry with backoff, a visible
   "reconnecting…" state, and idempotency keys on write requests (so a retried "claim
   job" can't double-claim). No offline data storage — if you're fully offline, actions
   queue in memory until the tab is closed, then are lost.
3. **Full offline-first (PWA + service worker + local queue):** actions taken while
   offline are persisted locally (IndexedDB) and synced when connectivity returns, survive
   a closed tab/refresh. Real resilience, real engineering cost.

## Recommendation
**Option 2 now, option 3 only if real-world usage shows it's needed.** This is a
dispatcher cockpit, not a field app — dispatchers are expected to be at a desk/counter
with intermittent-but-not-truly-offline connections. Full offline-first (option 3) is
the kind of investment that pays off for a driver-facing mobile app, not this one; option
2 directly answers "survives a slow connection" (the literal NFR-1 wording) without the
service-worker/local-database complexity of option 3, and every idempotency key it
introduces (e.g. for job-claim requests) is also just good API design regardless of this
decision.

**If approved, this implies (for the first ticket that touches it):**
- API: idempotency keys on state-changing endpoints likely to be retried (claim, status
  change).
- Frontend: a shared fetch wrapper with timeout + backoff retry, and a visible
  connection-state indicator on the board.
- No service worker, no offline data persistence, no PWA manifest — explicitly out of
  scope under this recommendation.

## Consequences (if approved as recommended)
- **Cost:** low — no new dependencies, mostly a fetch-wrapper + idempotency-key pattern.
- **Risk:** if dispatchers turn out to work somewhere with real offline gaps (not just
  slow connections), option 2 won't cover it — revisit with option 3 if that surfaces.
- Needs explicit owner sign-off since it sets the bar for what "survives a slow
  connection" means for every future ticket's Definition of Done.
