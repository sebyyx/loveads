# Spec — Platform-agnostic ecommerce wording + Google Analytics as a data source

_Date: 2026-08-02 · Scope: `copilot/index.html`, `index.html`, `llms.txt`_

## Problem

The Copilot landing hard-codes **Shopify** as the store integration (~20 occurrences), plus 7 on the
homepage and 4 in `llms.txt`. More ecommerce platforms will be connectable, and **Google Analytics**
is being added as a data source. The copy needs to stop implying Shopify is the only option, without
promising integrations that do not exist.

## Principle

- **Visible marketing copy is generic** — "your store" in sentences, "Ecommerce Platform" in the
  integration pills.
- **Where a reader asks a concrete question, answer concretely** — the "which channels do you
  support" FAQ and `llms.txt` name **Shopify and Magento, with more being added**. This keeps the
  brand-name SEO signal and gives AI crawlers real facts.
- **Google Analytics is a fourth peer source** — same visual weight as Google Ads / Meta / store.
  No new section, no new claim about what GA4 adds beyond being connectable.

## Changes

### 1. `copilot/index.html` — visible copy

| Location | From | To |
|---|---|---|
| H1 hero | `Google Ads, Meta & Shopify.` | `Google Ads, Meta & your store.` |
| Sub-hero | `...Google Ads, Meta Ads and Shopify...` | `...Google Ads, Meta Ads, Google Analytics and your ecommerce platform...` |
| Unify section title | `Google + Meta + Shopify → one screen` | `Google, Meta, Analytics & your store → one screen` |
| Features tab 0 heading | `Unify Google, Meta & Shopify` | `Unify ads, analytics & your store` |
| How it works — Connect | `link Google Ads, Meta and Shopify` | `link Google Ads, Meta, Google Analytics and your ecommerce platform` |
| Audience card | `running your own Google, Meta and Shopify` | `running your own Google, Meta and online store` |
| FAQ — what is Copilot | `...and Shopify in one place...` | `...Google Analytics and your ecommerce platform in one place...` |
| FAQ — which channels | `Google Ads, Meta Ads and Shopify are supported...` | `Google Ads, Meta Ads, Google Analytics and your ecommerce platform — Shopify and Magento today, with more being added — are supported...` |
| FAQ — who is it for | `running their own Google, Meta and Shopify` | `running their own Google, Meta and online store` |
| Final CTA | `Connect Google, Meta and Shopify in one click` | `Connect Google, Meta, Analytics and your store in one click` |
| Sticky CTA | `Connect Google, Meta & Shopify in one click.` | `Connect Google, Meta & your store in one click.` |

### 2. `copilot/index.html` — integration pills (2 locations: Unify section, Features tab 0)

Three pills become four:

```
[Google Ads] [Meta Ads] [Ecommerce Platform] [Google Analytics] → [Copilot]
```

- Store pill: icon `bi-bag-fill` (Shopify green `#95bf47`) → `bi-shop` in neutral `var(--cp-muted)`.
- New GA pill: `bi-bar-chart-line-fill` in GA orange `#e8710a`.
- Four pills must wrap cleanly at 375 px; add a CSS tweak only if verification shows overflow.

### 3. `copilot/index.html` — SEO & structured data

`<title>`, meta `description`, meta `keywords`, `og:title`, `og:description`, `twitter:title`,
`twitter:description`; JSON-LD `SoftwareApplication.description`, `featureList[0]`, and the three
`FAQPage` answers listed above.

**Constraint:** each JSON-LD FAQ answer must stay character-identical to its visible counterpart, or
Google flags the markup as non-compliant.

Keywords keep `Shopify` and gain `Magento`, `Google Analytics`, `ecommerce analytics`.

### 4. `index.html` (homepage) — 6 locations

Meta `description`, meta `keywords`, `og:description`, `twitter:description`, JSON-LD
`ProfessionalService.description`, and the Copilot showcase paragraph.

**Explicitly unchanged:** the `Shopify Plus` tag in the portfolio card (line ~314). That describes a
real delivered project, not an integration promise.

### 5. `llms.txt` — 4 locations

Names platforms explicitly: `Google Ads, Meta Ads, Google Analytics and ecommerce platforms
(Shopify, Magento)`. LLMs need concrete facts, not generic phrasing.

## Out of scope

`copilot.css` (icon colours are inline), the POAS calculator, pricing tiers and their `Offer` schema,
the animated hero mock, all other JS.

## Verification

1. `grep -rin shopify` across the repo — only remaining hits should be the portfolio tag in
   `index.html`, the FAQ/`llms.txt` platform lists, and the meta keywords.
2. Serve locally (`python3 -m http.server 8765`), screenshot `/copilot` desktop + emulated mobile.
3. Confirm the four pills wrap without horizontal overflow at a real 375 px viewport (per
   `docs/WORKLOG.md` §9: inject a 375 px width override at emulated 500 px and check
   `getBoundingClientRect().right`).
4. Diff each visible FAQ answer against its JSON-LD twin — they must match exactly.
