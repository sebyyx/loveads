# Spec — One scroll-driven moment on /copilot (POAS section)

_Date: 2026-08-02 · Scope: `copilot/index.html`, `includes/css/page/copilot.css`_

## Problem

`/copilot` has 15 sections and **all 15 arrive the same way** — a `.reveal` fade-up on an
IntersectionObserver with a small `--d` stagger. Uniform motion means no hierarchy: everything is
equally emphasised, so nothing reads as important. The page feels flat rather than considered.

The fix is not more motion. It is **one moment that earns its animation**, with everything else quiet.

## The moment: POAS

The `REAL PROFIT / POAS` section (`copilot/index.html` ~line 288) carries the product's central
argument — ROAS flatters you, POAS tells the truth. Today its five stat rows fade in together and all
`data-count` numbers animate at once, so the argument arrives pre-resolved. Sequencing the rows turns
a static claim into a demonstration.

Rejected alternatives: the Decision Loop (a valid second moment — deferred, two moments dilute each
other on a first pass), How It Works (generic copy, would read as repetition), and the hero (already
has the auto-cycling mock; scroll treatment would fight it).

## Choreography — desktop (≥900 px)

ScrollTrigger pins the section for **~140 % of viewport height**, `scrub: true` (reversible). The
left column stays fixed; the right-hand `.cp-stat-card` builds row by row:

| Scrub progress | Row | Intent |
|---|---|---|
| 0–20 % | Revenue €41,000 | starting point |
| 20–40 % | Ad spend €10,000 | context |
| 40–60 % | **ROAS 4.1×** | looks great — the false comfort |
| 60–80 % | Product & fulfilment cost **−€22,000** | the hit |
| 80–100 % | **POAS 1.9×** (crimson) | the truth |

Each row animates `opacity 0→1`, `y 12px→0`. The last two rows get slightly more scroll distance —
that is where the argument lands.

**Reuse:** the rows already carry `data-count`, and the existing `animateCount(el)` is idempotent
(guards on `el.dataset.done`). The timeline calls it per row instead of reimplementing counting. Side
benefit: numbers no longer all start at once.

## Choreography — mobile (<900 px)

No pin. Pinning on phones reads as scroll hijack. The same timeline runs once when the card enters
the viewport, with a fixed duration and 0.1 s stagger between rows. Same message, no stuck feeling.

## Robustness

**Governing rule: the hidden state is applied only from JS, never from CSS.** GSAP sets
`gsap.set(rows, {opacity: 0})` at init. If GSAP fails to load, if the CDN is down, if JS is blocked —
the rows simply stay visible as they are today. There is no code path that yields an empty section.

1. Guard on `window.gsap && window.ScrollTrigger` before initialising anything.
2. `prefers-reduced-motion: reduce` → ScrollTrigger never initialises; rows stay static and
   `animateCount` behaves exactly as it does now.
3. **Fix the missing `.js` gate** (open follow-up, `docs/WORKLOG.md` §10). `.cp .reveal{opacity:0}` is
   currently unconditional, so with JS disabled everything below the hero is invisible. Becomes
   `.js .cp .reveal{opacity:0}` plus an inline `<script>` in `<head>` adding the class — the same
   pattern the homepage already uses. Adding an external dependency to a page with no no-JS fallback
   is not acceptable; this ships in the same change.

## Cost & integration

GSAP core + ScrollTrigger ≈ **36 KB gzipped**, loaded `defer` from jsDelivr — the CDN the page already
uses for Bootstrap and Bootstrap Icons, so no third point of failure is introduced. The new code goes
into the existing `<script>` block at the end of the page, beside the other modules.

**Untouched:** hero mock, POAS calculator, feature tabs, the other 14 sections, the nav.

## Primary risk

ScrollTrigger's pin inserts a `pin-spacer` wrapper into the DOM, and the nav is `position: sticky`.
This is the classic place the combination breaks. Tested explicitly.

## Verification

1. Desktop screenshots at 0 / 50 / 100 % of the pin — rows appear in order, left column stays fixed.
2. Mobile 375 and 500 — no pin, rows still sequence, `scrollWidth == clientWidth`.
3. **JS fully blocked** — the whole page, including all `.reveal` content, is visible.
4. **GSAP blocked but page JS working** — stat rows visible, no console error cascade.
5. `prefers-reduced-motion: reduce` — no pin, no scrub, content readable.
6. Sticky nav stays correctly pinned above and through the pinned section.
