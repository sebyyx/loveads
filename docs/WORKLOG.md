# loveads.ro — Work Log & Project Reference

_Last updated: 2026-06-16_

This document captures everything built on **loveads.ro** in this work cycle, plus the
non-obvious operational details (deployment, verification, gotchas) needed to continue safely.

---

## 1. What the site is

- **loveads.ro** — static site (HTML/CSS/JS + one PHP form handler) for **Love Ads Marketing SRL**.
  - `index.html` — homepage: product-strategy consulting + a **LoveAds Copilot** showcase section.
    **Redesigned 2026-06-15** by the design/UX team — new system on `site.css` (Geist, light, crimson),
    **no Bootstrap**. Contact form re-wired to `contact.php`; GTM + full SEO/JSON-LD preserved.
  - `copilot/index.html` — dedicated **LoveAds Copilot** landing page (the SaaS product).
  - `privacy-policy.html`, `terms.html` — legal pages. **Restyled 2026-06-15** onto `site.css` to match
    the new homepage (were Bootstrap/`main.css`).
  - `contact.php` — contact form handler (emails `sebastian.cosmor@loveads.ro`).
  - `site.css` + root assets (`loveads-wordmark.png`, `logo-*`, `favicon-32.png`, `apple-touch-180.png`)
    — the new design system. `includes/css/page/main.css` is now **legacy** (no live page references it).
- **copilot.loveads.ro** — the live Copilot app (separate; we only link to it).
- Company: Love Ads Marketing SRL · CUI RO39641531 · J40/10343/2018 · Voluntari, Ilfov, RO.

---

## 2. Deployment (IMPORTANT — read before pushing)

- Deploy is **cPanel → Git Version Control**, triggered **manually by the site owner** (Sebastian).
  A `git push` to GitHub (`sebyyx/loveads`, branch `main`) does **NOT** auto-deploy.
- Workflow: Claude commits + pushes to `main` → **owner clicks Deploy in cPanel** → live in ~1–2 min
  (propagation is not instant; wait a minute or two before verifying live).
- `.cpanel.yml` runs: `rsync -av --delete . $DEPLOYPATH` (excludes `.git`, `.cpanel.yml`, `config.php`).
  - **`--delete` means anything not tracked in git is removed from `public_html` on deploy.**
  - Legacy **untracked** files exist locally and are intentionally NOT committed (old 2018–2019 assets:
    `includes/fonts/`, `includes/plugin/`, `includes/js/`, `includes/css/common/`, `includes/css/plugin/`,
    `includes/css/page/default.css`, `hai-sa-ne-cunoastem.php`, `loveads.jpg`, `NOTES.md`). The live site
    does not depend on them (homepage + legal use `site.css` + Geist; `/copilot` uses `copilot.css` +
    CDN Bootstrap/Icons + Geist).
- `.htaccess` forces HTTPS and pins PHP 8.3. `NOTES.md` is gitignored (local scratch).

---

## 3. Contact form anti-spam (`contact.php` + `index.html`)

Spam bots were filling the form with random strings. Four silent layers — a failed check returns
`{"success":true}` (so bots don't adapt) but **does not send mail**:

1. **Honeypot** — hidden `company_website` field; if filled → bot.
2. **JS token** — JS adds `_js=1` on submit; bots that POST directly miss it.
3. **Time-to-submit** — `_elapsed` < 3000 ms → bot.
4. **Content sanity** — invalid email, links in name/message, or newline header-injection → rejected.

Owner confirmed legitimate submissions still arrive.

---

## 4. Brand assets

Generated from `~/Downloads/loveads_logo.pdf` (2 pages) and `~/Downloads/app-icon-charcoal.png`
(crimson heart on charcoal). Tools used: **Swift + CoreGraphics** (no poppler/ImageMagick available),
`sips`, `qlmanage`. Scripts were one-offs in `/tmp` (not kept in repo).

- `includes/images/loveads-logo.png` — **white** wordmark (for dark backgrounds). White "Love" was
  produced by recoloring PDF page 1's charcoal glyphs → white (page 2 had a baked-in dark background).
- `includes/images/loveads-logo-dark.png` — **charcoal** wordmark (for light backgrounds).
- Favicons: `favicon.ico` (root), `favicon-16/32`, `apple-touch-icon` (180), `icon-192`, `icon-512`.
- `og-default.jpg` / `og-copilot.jpg` — 1200×630 Open Graph share images.
- `site.webmanifest` — PWA manifest (theme `#d0224c`).

Logo usage (after 2026-06-15 redesign): the **new homepage + legal pages** use `loveads-wordmark.png`
(charcoal "Love" + crimson "Ads", for the light theme) in nav/footer. The Copilot landing nav uses the
charcoal wordmark + "Copilot" suffix. The old white `loveads-logo.png` (for the old dark homepage) is no
longer used on the homepage.

---

## 5. SEO & GEO

**SEO** (all pages): full title/description/keywords/canonical/robots meta, Open Graph + Twitter cards,
favicons. JSON-LD:
- Homepage: `Organization` + `WebSite` + `ProfessionalService`.
- Copilot: `SoftwareApplication` (with 4 pricing `Offer`s) + `BreadcrumbList` + `FAQPage`.
- `sitemap.xml` + `robots.txt` (sitemap referenced).

**GEO** (AI answer engines):
- `robots.txt` explicitly **allows** AI crawlers (GPTBot, OAI-SearchBot, ClaudeBot, anthropic-ai,
  PerplexityBot, Google-Extended, Applebot-Extended, CCBot, Bytespider, meta-externalagent…).
- `llms.txt` — plain-language description of company, product, pricing, key concepts for LLMs.
- Visible **FAQ** section on `/copilot` backing the `FAQPage` schema.

---

## 6. Copilot landing (`copilot/index.html`)

Design: **light "control tower"** aesthetic, crimson accent `#d0224c`, **Geist** + Geist Mono fonts —
mirrors the app for a continuous site→app transition. Styles in `includes/css/page/copilot.css`.

Section order: Hero → Unify logos → 3 questions → POAS → **POAS calculator** → Leaks & opportunities →
Decision loop → **Features (tabs)** → How it works → Audience → Pricing (4 cards, Founding highlighted)
→ FAQ → Trust + final CTA → **sticky CTA** → footer.

### Interactive / dynamic elements (vanilla JS, no deps; all respect `prefers-reduced-motion`)
1. **POAS calculator** (`#poasCalc`) — sliders: monthly revenue, ad spend, product cost %.
   - `ROAS = revenue / spend`
   - `POAS = (revenue − revenue×cost%) / spend`
   - `profit = revenue − revenue×cost% − spend`
   - Defaults 41000 / 10000 / 54% → ROAS 4.1×, POAS 1.9×, profit €8,860. Profit box turns red if POAS < 1×.
2. **Animated hero mock** (`#heroMock`) — 3 views (Overview / Money leak / AI report) auto-cycling every
   4.2 s, pause on hover, clickable; KPIs count up. **Fixed 2026-06-14:** the 3 views had different
   heights (602/346/316 px) and on mobile never paused (no `mouseenter`), so the card — and the whole
   page below — jumped ~286 px every cycle. Now the views are grid-stacked in one cell
   (`.cp-mock-body{display:grid}` + `.cp-view{grid-area:1/1}`, toggled via opacity), so the height is
   stable. **Note: `/copilot` reveal is not `.js`-gated — see §10.**
3. **Feature tabs** (`#featTabs`) — 5 themed tabs (Unify / Real profit / Leaks & growth / Reports /
   Stay ahead), each with its own visual panel (replaced the old 9-card grid).
4. **Sticky CTA** (`#stickyCta`) — "Start free" bar appears after 700 px scroll, hides over the footer.
5. **Count-up numbers** — any `[data-count]` element animates when scrolled into view
   (supports `data-prefix`, `data-suffix`, `data-decimals`).

### CTAs
- Conversion CTAs ("Open Copilot", "Start free", calculator CTA) → **`https://copilot.loveads.ro/signup`**.
- Utility links ("Open app" in footer) and JSON-LD `sameAs` → app root `https://copilot.loveads.ro`.

---

## 7. Mobile fixes

Reported: horizontal scroll ("jiggle") + content shifted/clipped on phones. Root causes fixed:
- `html` lacked `overflow-x: hidden` (only `body` had it) → added `overflow-x: hidden` + `max-width:100%`.
- Bootstrap `g-5`/`g-4` rows directly in a full-width `.container` overflow ~12 px each side on phones →
  shrink `--bs-gutter-x` to `1.5rem` under 576 px.
- Copilot decision-loop arrows overflowed → loop stacks vertically under 768 px.

Verified at a **real 375 px** device-emulated viewport: `scrollWidth == clientWidth` on both pages.

**2026-06-15 (new homepage):** horizontal drag returned on mobile. Root cause was the **sticky nav**:
only `.nav-links` was hidden on mobile, but `.nav-cta` (Open App + Let's talk + hamburger, ~273 px)
stayed, so brand + CTA exceeded ~375 px — and since the nav is `position:sticky`, the page dragged
sideways at any scroll position (it was noticed "in the portfolio area" but the cause was the nav).
Fix: `@media(max-width:680px){.nav-cta .btn:not(.nav-toggle){display:none}}` + "Open App" added to the
mobile menu, plus `overflow-x:clip` (NOT `hidden` — that breaks sticky) on body as a fallback, and
`min-width:0` on form `.field` + stacking the portfolio `.work` items. **Lesson: first suspect for
mobile horizontal drag = the sticky nav's buttons, not the section where you notice it.**

---

## 8. Design tokens

- **Homepage + legal (light, since 2026-06-15 redesign):** canvas `#f4f3f0`, ink `#19191b`, crimson
  accent `#d0224c`, **Geist** + Geist Mono. Defined in `site.css` `:root`. (The old homepage was dark
  `#0f172a` / indigo `#6366f1` / Inter on `main.css` — now replaced.)
- **Copilot landing (light):** canvas `#fff`/`#f7f7f9`, crimson `#d0224c`, ink `#15151a`, Geist font.
- The whole site now shares one crimson + Geist identity (homepage, copilot, legal).
- **About-section skill chips** (`.chip` in `site.css`, 2026-06-16): Geist **sans** (not mono),
  `border-radius:9px` (not a 999px pill), a crimson `::before` dot (`var(--accent)`, 6px),
  `var(--text)` label on `var(--surface)` with a `var(--line-2)` border. Labels were also shortened
  (Product Discovery, Product Strategy, Roadmapping, Requirements & Specs, Technical Advisory, Team &
  Vendor Selection, Stakeholder Alignment, Go-to-Market). Verified live on 390 px mobile, no overflow.

---

## 9. Verification tooling (notes for next time)

- **Chrome `--headless=new` floors the viewport at ~500 px wide — even with CDP
  `Emulation.setDeviceMetricsOverride {width:375}` AND `--window-size=375`** (`window.innerWidth`
  reports ~451–500 regardless; old `--headless` floors too). So you **cannot** reproduce sub-500 px
  horizontal overflow visually here. The mobile layout (`@media max-width:680px`) still activates at
  ~500 px, so arrangement is verifiable.
- **To find real <500 px overflow:** at emulated mobile 500, inject
  `html,body{width:375px!important;max-width:375px!important;overflow-x:visible!important}`, then list
  elements with `getBoundingClientRect().right > 376`. This is how the 2026-06-15 nav overflow was found.
- Drive Chrome via CDP (Node 24 has global `fetch` + `WebSocket`); `Page.captureScreenshot
  {captureBeyondViewport:true}`. Disable `scroll-behavior:smooth` before scripted `scrollTo` to far
  targets or the screenshot fires mid-animation.
- 100vh heroes fill the headless window — collapse `min-height` (or use full-page capture) to see lower
  sections; `.reveal`/`.fade-up` start at `opacity:0` so force them visible for static screenshots.
- Local serve: `python3 -m http.server 8765` from repo root (root-relative `/includes/...` paths need it).

---

## 10. Open follow-ups / ideas

- **Replace mock data with real anonymized screenshots/GIF** from `copilot.loveads.ro` (brief §11) —
  biggest remaining conversion lift on the landing.
- If advanced bots get past the form anti-spam → add **Cloudflare Turnstile** (free, no cookie banner).
- Optional polish: tune hero cycle speed, calculator defaults, tab order.
- Consider committing the legacy untracked assets or deleting them (currently neither tracked nor deployed).
  Also `includes/css/page/main.css` is now legacy (old homepage), no live page references it.
- **`/copilot` reveal is not `.js`-gated** (`.cp .reveal{opacity:0}`) — content below the hero depends on
  JS with no no-JS fallback. The new homepage solved this with a `.js` gate; consider porting it to copilot.
- Resolved this cycle: homepage no-JS fallback (new `.js .reveal` design); Copilot hero mock height jump
  (the 3 auto-cycling views had different heights → ~286 px page jump every 4.2 s; now grid-stacked to a
  stable height); homepage accent unified to crimson; mobile horizontal overflow (sticky nav).

---

## 11. Commit history (this cycle, branch `main`)

- `Add anti-spam protection to contact form`
- `Add LoveAds Copilot landing page + homepage showcase`
- `Add brand logo, favicons, and SEO/GEO optimization`
- `Fix mobile horizontal overflow and layout`
- `Make Copilot landing interactive and engaging`
- `Relabel calculator input to "Monthly revenue"`
- `Add project work log; exclude docs/ and NOTES.md from deploy`
- `Switch homepage action color to LoveAds Copilot crimson`
- `Fix mobile scroll jump on /copilot hero mock`
- `Replace homepage with new design-team redesign`
- `Fix mobile horizontal overflow and portfolio layout on homepage`
- `Fix mobile horizontal overflow: hide nav CTA buttons on phones`
- `Restyle legal pages to match new homepage design`
- `Update docs and sitemap for the homepage redesign`
- `Shorten About-section skill chips`
- `Restyle About-section chips: sans font, 9px corners, crimson dot`
