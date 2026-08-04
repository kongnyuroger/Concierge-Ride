# 0006 — Live Shared State / Realtime

## Status
Accepted (mechanism) — 2026-08-04

## Context
Multiple dispatchers share one live view of the job board — a job claimed or updated by
one dispatcher must appear for everyone else within seconds, without a manual refresh.

## Options considered
1. **Laravel Reverb + Laravel Echo** (WebSockets, first-party Laravel).
2. **Polling** (frontend re-fetches every N seconds). Simplest, but "within seconds"
   either means aggressive polling (wasteful, still laggy) or an unacceptable delay.
3. **Third-party realtime SaaS** (Pusher, Ably). Less ops burden than self-hosting, but
   a recurring per-connection cost and another vendor account for something Laravel now
   ships natively.

## Decision
**Option 1**, already fixed in the stack (ADR 0001). Not yet installed — no ticket has
needed a live board yet.
- **Reverb** runs as the WebSocket server (self-hosted, MIT-licensed, no per-connection
  vendor fee); **Echo** is the frontend client library, paired with SvelteKit via a thin
  wrapper (no official Svelte binding, but Echo is framework-agnostic).
- Broadcast on the model events that matter for shared state (job claimed, status
  changed, reassigned) via Laravel's standard `ShouldBroadcast` events — not a custom
  polling job.
- Rejected polling because "within seconds" is explicitly a stack-level bar (CLAUDE.md),
  and rejected Pusher/Ably because Reverb removes a vendor dependency and per-seat/
  per-connection cost for a self-hostable equivalent already in the Laravel ecosystem.

## Consequences
- **Cost:** Reverb itself is free; it needs a running process (not just PHP-FPM request/
  response) — on Render this means a **second service** (or a persistent connection
  process alongside the API), which ADR 0002 doesn't yet provision. First realtime
  ticket needs to extend the hosting setup, not just add backend code.
- **Risk:** WebSocket connections don't survive Render's free-tier cold-start/sleep
  behavior well — a dispatcher's connection would drop when the service spins down.
  Realtime in staging may need a paid Render plan sooner than the rest of the stack.
- No channels/events exist yet; this ADR fixes the mechanism, not the channel/event
  design, which belongs to the ticket that first needs a live board.
