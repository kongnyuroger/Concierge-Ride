# 0008 — Messaging Provider (WhatsApp / SMS)

## Status
**PROPOSED — pending owner approval**

## Context
Dispatchers need to notify customers and drivers (booking confirmations, driver-en-route
alerts, etc.) via WhatsApp and/or SMS, in French and English. No provider is chosen or
integrated yet. This is a genuinely open decision — no code or prior ADR commits to one.

## Options considered
1. **Africa's Talking.** Pan-African API covering SMS, WhatsApp, Voice, USSD from one
   vendor/one integration, with established local-carrier routing across the continent
   including Cameroon.
2. **Twilio.** Mature, best-documented WhatsApp Business API integration globally, but a
   non-African vendor — SMS routes into MTN/Orange Cameroon are typically costlier and
   less locally optimized than an Africa-first provider.
3. **Direct Meta WhatsApp Cloud API + a separate local SMS gateway** (e.g. Orange's own
   SMS API, or a local aggregator). Cheapest per-message (no reseller markup on
   WhatsApp), but two integrations, two sets of credentials/webhooks to maintain.
4. **Local/regional specialist (Messaggio, Africala, Termii).** Claim strong direct-
   operator Cameroon delivery rates; less track record/community documentation than
   Africa's Talking to evaluate against from the outside.

## Recommendation
**Option 1 (Africa's Talking).** One vendor, one API, for both SMS and WhatsApp — lowest
integration surface area for a small team, with Africa-specific carrier relationships
that a global vendor like Twilio doesn't prioritize. Option 3 is worth revisiting later
purely to cut WhatsApp cost once volume is real, but doubling integrations from day one
isn't worth it for M0/M1. **Before committing:** get a live quote/sandbox test against
actual MTN/Orange Cameroon numbers — published pricing pages don't always reflect real
in-country delivery rates, and this ADR's recommendation should be confirmed against a
real test, not just documentation.

## Consequences (if approved as recommended)
- **Cost:** per-message/per-conversation fees (SMS + WhatsApp conversation pricing) —
  needs a real quote before budgeting; likely the largest recurring per-transaction cost
  in the stack once volume grows, unlike the mostly-fixed hosting costs in ADR 0002.
- **Risk:** WhatsApp Business API requires Meta template approval for outbound
  notifications outside a 24h customer-initiated window — first messaging ticket needs
  to account for template review lead time, not assume instant send capability.
- **Risk:** vendor lock-in is moderate — Africa's Talking's SMS/WhatsApp API shape is
  broadly similar to competitors, so switching later is a rewrite of one integration
  module, not a systemic change.
