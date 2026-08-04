# Spec — Cookie consent bar with Google Consent Mode v2

_Date: 2026-08-04 · Scope: `index.html`, `copilot/index.html`, `terms.html`, `privacy-policy.html`,
`site.css`, `includes/css/page/copilot.css`_

## Problem

GA4 (`G-1BF1ET6PWQ`) went live on 3 August and sets analytics cookies on every page. Under GDPR and
ePrivacy those require prior consent from EU visitors, and the privacy policy already names consent
as the legal basis — but nothing on the site asks for it. Until the GA4 deploy this was theoretical,
because the previous tag was dead and collected nothing. It is now a real gap.

Separately, `gtag.js` is 165 KB and sits on the critical path, costing ~450 ms of main-thread work.

## Decisions taken

- **Non-blocking bottom bar.** Consent Mode already prevents collection before a choice, so blocking
  the page buys nothing and costs goodwill on a site that sells trust.
- **Two buttons, equal weight.** One non-essential category exists (analytics); category toggles
  would be compliance theatre. Decline and Accept get identical dimensions and shape — a quiet
  "decline" link beside a prominent "accept" button is exactly what regulators penalise.
- **Plain language.** The product promises "in plain language, not dashboards"; the banner honours it.
- **English**, matching the rest of the site.

## Visual design

A fixed full-width bar on `--instrument-black` (`#19191b`) with `--on-ink` text and a hairline top
rule. In this system, dark full-bleed sections are the page's punctuation, so a charcoal bar reads
immediately as the system speaking rather than as more content. No corner radius — it is full-bleed.

Entry: 16px translate from below plus a fade, 300 ms on `cubic-bezier(.2,.7,.3,1)`. Disabled under
`prefers-reduced-motion: reduce`, per the system rule that every animation respects it.

Copy:

> **We use Google Analytics to see which pages people read.**
> That's it — no ads, no profiles, nothing sold on. [Privacy policy](/privacy-policy.html)
>
> `[ Decline ]` `[ Accept ]`

Buttons reuse the existing `.btn` shape (50 px tall, 8 px radius). Accept takes the crimson primary
treatment; Decline takes a bordered treatment on the dark ground. Both are real buttons of the same
size.

## Consent Mode v2 — ordering is load-bearing

1. **Inline in `<head>`, before anything else:** `gtag('consent','default', …)` with `ad_storage`,
   `analytics_storage`, `ad_user_data` and `ad_personalization` all `denied`, plus
   `wait_for_update: 500`. Roughly 300 bytes.
2. **After the `load` event (or a 2 s fallback):** inject `gtag.js`.
3. **On Accept:** `gtag('consent','update', { analytics_storage:'granted', ad_storage:'granted',
   ad_user_data:'granted', ad_personalization:'granted' })`.

Step 1 must run before `gtag.js` or Consent Mode does not apply at all. Step 2 is the performance
win: 165 KB leaves the critical path. **Stated cost: visitors who leave within ~2 s are not counted.**

## Choice storage

`localStorage` under `loveads_consent`, holding the decision and an ISO timestamp. Not a cookie —
setting a cookie to record that someone refused cookies is self-defeating.

Re-ask after **6 months**, matching European guidance. No nagging in between.

## Withdrawal

A "Cookie settings" link in the footer of all four pages reopens the bar. GDPR grants an explicit
right to withdraw consent and the privacy policy already promises it; without this link a visitor who
declined has no route back.

## Conflict to resolve

`/copilot` has a sticky CTA (`#stickyCta`) that appears after 700 px of scroll, pinned to the bottom.
It would overlap the consent bar. While the bar is visible the sticky CTA stays hidden; it appears
once a choice is made. This is also the correct hierarchy — do not ask someone to sign up before
telling them what you collect.

## Out of scope

No category toggles, no preferences panel, no third-party consent platform. No banner when
JavaScript is disabled: GA4 does not run either, so there is nothing to consent to.

## Verification

1. First visit: bar appears, no `/g/collect` request fires, `gtag.js` loads only after `load`.
2. Accept: consent update fires, a `page_view` reaches `region1.google-analytics.com`, bar dismisses,
   choice persists across reload.
3. Decline: no collect request, bar dismisses, choice persists.
4. Footer link reopens the bar after a decision.
5. `/copilot`: sticky CTA stays hidden until a choice is made, then behaves as before.
6. No horizontal overflow at 375 px; contrast on the dark bar ≥4.5:1; Lighthouse Performance and
   Accessibility measured on live after deploy.
