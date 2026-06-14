# loveads.ro — Work Log & Project Reference

_Last updated: 2026-06-14_

This document captures everything built on **loveads.ro** in this work cycle, plus the
non-obvious operational details (deployment, verification, gotchas) needed to continue safely.

---

## 1. What the site is

- **loveads.ro** — static site (HTML/CSS/JS + one PHP form handler) for **Love Ads Marketing SRL**.
  - `index.html` — homepage: product-strategy consulting + a **LoveAds Copilot** showcase section.
  - `copilot/index.html` — dedicated **LoveAds Copilot** landing page (the SaaS product).
  - `privacy-policy.html`, `terms.html` — legal pages.
  - `contact.php` — contact form handler (emails `sebastian.cosmor@loveads.ro`).
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
    does not depend on them (it uses `includes/css/page/main.css` + CDN Bootstrap/Icons + Google Fonts).
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

Logo usage: white in homepage nav/footer (dark theme); charcoal + "Copilot" suffix in the Copilot
landing nav (light theme); white on privacy/terms nav.

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
   4.2 s, pause on hover, clickable; KPIs count up.
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

---

## 8. Design tokens

- **Homepage (dark):** bg `#0f172a`/`#1e293b`, indigo accent `#6366f1`, Inter font. Copilot showcase
  section reuses a crimson `#d0224c` product accent to stand apart.
- **Copilot landing (light):** canvas `#fff`/`#f7f7f9`, crimson `#d0224c`, ink `#15151a`, Geist font.

---

## 9. Verification tooling (notes for next time)

- **Chrome headless `--headless=new` floors the viewport at ~500 px wide.** A `--window-size=390` screenshot
  renders at ~500 px and crops to 390 → content looks falsely shifted/clipped. Don't trust narrow screenshots.
- For **true mobile** (375 px): use **CDP device emulation** via Node + Chrome `--remote-debugging-port`
  (`Emulation.setDeviceMetricsOverride {width:375, deviceScaleFactor:2, mobile:true}`), then
  `Page.captureScreenshot {captureBeyondViewport:true}`. Node 24 has global `fetch` + `WebSocket`.
- Overflow check: compare `document.documentElement.scrollWidth` to `clientWidth`.
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

---

## 11. Commit history (this cycle, branch `main`)

- `Add anti-spam protection to contact form`
- `Add LoveAds Copilot landing page + homepage showcase`
- `Add brand logo, favicons, and SEO/GEO optimization`
- `Fix mobile horizontal overflow and layout`
- `Make Copilot landing interactive and engaging`
- `Relabel calculator input to "Monthly revenue"`
