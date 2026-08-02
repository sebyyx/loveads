# Changelog — loveads.ro

Reverse-chronological record of what shipped. Operational detail, gotchas and the reasoning behind
each decision live in `WORKLOG.md`; this file is the index of *what changed, when, and why*.

---

## 2026-08-02

Four changes, all deployed and verified on the live site.

### Copilot messaging is no longer Shopify-specific

Copilot will connect to more than one store platform, so the hard-coded **Shopify** copy was
generalised and **Google Analytics** added as a fourth data source.

The rule applied: **visible marketing copy is generic, concrete answers stay concrete.** Sentences say
"your store", the integration pills say "Ecommerce Platform" — but the "which channels do you support"
FAQ and `llms.txt` still name **Shopify and Magento, with more being added**. That keeps the brand-name
SEO signal and gives AI crawlers real facts, without promising integrations that don't exist yet.

- `copilot/index.html` — title, meta description/keywords, Open Graph, Twitter, JSON-LD
  (`description`, `featureList`, three `FAQPage` answers) and 13 visible copy locations.
- Integration pills 3 → 4 in both places: store icon `bi-bag-fill` in Shopify green → `bi-shop` in
  neutral grey; new Google Analytics pill `bi-bar-chart-line-fill` in `#e8710a`.
- `index.html` — 6 SEO and copy locations. The **`Shopify Plus` portfolio tag stays**: it describes a
  real delivered project, not an integration claim.
- `llms.txt` — 4 locations, naming platforms explicitly.
- On phones the source→Copilot arrow now takes its own line and points down at the Copilot pill,
  instead of being orphaned beside the last source.

Verified: JSON-LD parses and all six FAQ answers match the visible text verbatim; pills wrap without
overflow at 375px.

### One scroll-driven moment on /copilot

All 15 sections arrived with the same `.reveal` fade-up, so nothing read as important. Rather than add
motion everywhere, one section now earns it: the **POAS stat card**, which carries the argument that
ROAS flatters you and POAS tells the truth.

- Desktop ≥900px pins the section for `+=140%` with scrub; the five stat rows reveal in order, and the
  last two (real product cost, then POAS) get 1.4× the scroll distance because that's where the
  argument lands.
- Phones get **no pin** — pinning reads as scroll hijack. The same timeline fires once on entry.
- Count-up reuses the existing idempotent `animateCount()`, called per row. The rows were unobserved
  from the page-wide count-up observer, which was otherwise firing the numbers while the rows were
  still invisible.
- GSAP 3.13 + ScrollTrigger from jsDelivr, `defer`, ≈36KB gzipped. No build step.

**The safety rule:** the hidden state is set from JS and never from CSS, behind a
`window.gsap && window.ScrollTrigger` guard and a `prefers-reduced-motion` check. A blocked CDN leaves
the rows plainly visible instead of an empty section.

### /copilot reveals are gated on `.js`

A long-standing gap, closed because the page now depends on an external animation library.
`.cp .reveal{opacity:0}` was unconditional, so with JavaScript disabled everything below the hero was
invisible. Now `.js .cp .reveal` plus an inline `<script>` in `<head>` — the pattern `site.css` already
used on the homepage. **Both pages are now safe without JS.**

### Hero BETA announcement pill

The hero opened with an eyebrow reading "Profit copilot", which just restated the H1 beneath it. It was
replaced by `● LoveAds Copilot — **Now in BETA**`.

A conic-gradient `::before` rotates every 5s behind an `inset:1px` `::after` fill, so only a 1px ring
stays lit and a crimson highlight sweeps around the border. Static rather than a link, so it doesn't
compete with the "Open Copilot" CTA below it. `/copilot` only. Under `prefers-reduced-motion` the sweep
becomes a plain soft-crimson ring.

Handed to the product team as a self-contained component spec with a live demo.

### Repo cleanup — 232 tracked files down to 33

Everything removed was unreachable from the live pages and was being rsynced into `public_html` on
every deploy.

| Removed | Size | Why |
|---|---|---|
| `includes/Swift/` (155 files) | 792KB | Swift Mailer. Nothing outside the directory referenced it; `contact.php` sends via `mail()`. Unmaintained since 2021 — an abandoned PHP library sitting in the webroot for no reason. |
| 43 images | 2.3MB | Assets of the 2018–2019 site. Referenced only by untracked, never-deployed files. |
| `includes/css/page/main.css` | 24KB | Old homepage stylesheet, unreferenced since the 2026-06-15 redesign. |

Verified after removal: all 32 file references across the four live pages resolve, both manifest icons
resolve, and loading `/`, `/copilot/`, `/privacy-policy.html` and `/terms.html` with a full scroll
produces zero failed requests and zero broken images.

**Decision recorded:** the untracked 2018–2019 files stay on the local machine. Git never held them, so
deleting them would be irreversible, and they never deploy. This closes a question that had been open
in the docs since June.

---

## 2026-06-16

- Restyled the About-section skill chips: sans font instead of mono, 9px corners instead of a pill, a
  crimson dot before each label. Labels shortened.

## 2026-06-15

- **Homepage redesigned** onto a new design system: `site.css`, Geist, light theme, crimson `#d0224c`,
  no Bootstrap. Contact form re-wired to `contact.php`; GTM and full SEO/JSON-LD preserved.
- Legal pages restyled onto the same system (they were Bootstrap/`main.css`).
- Fixed mobile horizontal overflow. **The cause was the sticky nav**, not the section where it was
  noticed: only `.nav-links` was hidden on phones, so brand + CTA exceeded 375px, and because the nav
  is `position: sticky` the page dragged sideways at any scroll position.
- Homepage accent unified to the Copilot crimson.

## 2026-06-14

- Fixed a mobile scroll jump on the Copilot hero mock: the three auto-cycling views had different
  heights (602/346/316px) and never paused on mobile, so the page jumped ~286px every 4.2s. The views
  are now grid-stacked into one cell for a stable height.
- Made the Copilot landing interactive: POAS calculator, feature tabs, sticky CTA, count-up numbers.

## Earlier

- Added the LoveAds Copilot landing page and the homepage showcase section.
- Added brand logo, favicons, and SEO/GEO optimisation (JSON-LD, `sitemap.xml`, `robots.txt` allowing
  AI crawlers, `llms.txt`).
- Added four-layer anti-spam to the contact form: honeypot, JS token, time-to-submit, content sanity.
  A failed check returns `{"success":true}` so bots don't adapt, but sends no mail.
