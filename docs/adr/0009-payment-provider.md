# 0009 — Payment / Mobile-Money Provider (MTN, Orange)

## Status
**PROPOSED — pending owner approval**

## Context
Cameroon digital payments are effectively a duopoly: MTN Mobile Money (~12M users) and
Orange Money (~8M users). Both need to be supported from day one. No provider is chosen
or integrated yet — genuinely open.

## Options considered
1. **Integrate MTN MoMo API and Orange Money API directly**, one integration each.
   Lowest per-transaction fee (no aggregator markup), but two separate merchant
   onboarding/KYC processes, two API shapes to maintain, two sets of credentials and
   webhook handling, doubling the surface area for a small team.
2. **Aggregator: CinetPay.** Pan-African, strong francophone-Africa presence, one
   integration bundles MTN MoMo + Orange Money + cards behind one checkout/API.
3. **Aggregator: Monetbil.** Cameroon-specific mobile-money aggregator, simplified MTN +
   Orange integration, narrower geographic focus than CinetPay (which may mean tighter
   local support, or may mean less resilience — needs a direct evaluation).
4. **Aggregator: PayDunya.** Covers Cameroon plus a few other Francophone markets;
   similar shape to CinetPay, less specifically documented for this evaluation.

## Recommendation
**Option 2 (CinetPay), with option 3 (Monetbil) as the fallback to trial in parallel.**
One integration instead of two cuts the engineering and compliance burden materially for
a small team, and CinetPay's aggregator markup is a reasonable trade for that. Recommend
running a real sandbox trial against **both** CinetPay and Monetbil before final
sign-off — Monetbil's Cameroon-only focus could mean better local support/lower fees for
our exact use case, but that needs a real quote, not just a documentation comparison.
**Do not build direct dual integration (option 1)** unless a concrete cost/reliability
problem with aggregators shows up later — that's a reversible decision, aggregator lock-
in is not architecturally deep (payment provider should sit behind one internal
interface regardless of which vendor is chosen).

## Consequences (if approved as recommended)
- **Cost:** aggregator transaction fee (typically a percentage + fixed fee per
  transaction) — needs a real quote from CinetPay/Monetbil before this can be budgeted
  alongside hosting (ADR 0002) and messaging (ADR 0008) costs.
- **Risk:** this is money moving in and out of the business — the first payment ticket
  must apply NFR-6's data-layer enforcement standard (ADR 0003) especially strictly:
  payment status transitions, idempotent webhook handling, and reconciliation need
  DB-layer guarantees, not just controller-level checks.
- **Risk:** whichever aggregator is chosen, isolate it behind a single internal payment-
  provider interface/service class so switching vendors later (e.g. if fees or
  reliability disappoint) doesn't ripple through the booking/job code.
- No payment code exists yet; this ADR sets the vendor direction for the first payment
  ticket, not the integration design itself.
