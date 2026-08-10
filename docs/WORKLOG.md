# loveads.ro — Work Log & Project Reference

_Last updated: 2026-08-10_

A dated summary of what shipped lives in `CHANGELOG.md`; this document captures the
non-obvious detail behind it.

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
  - **Repo cleanup 2026-08-02: 232 tracked files → 33.** Removed `includes/Swift/` (155 files, 792 KB —
  Swift Mailer, dead: `contact.php` uses `mail()` directly and the library is unmaintained since 2021),
  43 orphaned images (2.3 MB, assets of the 2018–2019 site) and `includes/css/page/main.css`. All were
  being rsynced into `public_html` on every deploy. Recoverable from git history.
  **To audit again:** list tracked images whose basename appears in none of the live pages/CSS, then
  confirm the inverse — that every `src`/`href`/`url()` in the live pages still resolves — and load all
  four pages with `Network.responseReceived` to catch 404s. Checking only one direction is how you
  delete something that is still used.
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

### Platform-agnostic wording + Google Analytics (2026-08-02)

Copilot will connect more than one store platform, so hard-coded **Shopify** copy was generalized and
**Google Analytics** added as a fourth data source. Spec:
`docs/superpowers/specs/2026-08-02-copilot-platform-agnostic-design.md`.

Rule applied: **visible marketing copy is generic, concrete answers stay concrete.**
- Sentences say "your store"; the integration pills say "Ecommerce Platform".
- The "which channels do you support" FAQ and `llms.txt` still name **Shopify and Magento, with more
  being added** — keeps the brand-name SEO/GEO signal without promising integrations that don't exist.
- Pills (2 places: Unify section + Features tab 0) went 3 → 4: store icon `bi-bag-fill` in Shopify
  green `#95bf47` → `bi-shop` in neutral `var(--cp-muted)`; new GA pill `bi-bar-chart-line-fill` in
  `#e8710a`. They wrap on their own, so no layout CSS was needed — the one `copilot.css` change is
  cosmetic: under 767 px `.cp-logo-arrow` gets `flex-basis:100%` + `rotate(90deg)` so it takes its own
  line and points down at the Copilot pill instead of being orphaned beside the last source. Same
  treatment `.cp-loop-arrow` already had.
- Touched `copilot/index.html` (title, meta, OG, Twitter, JSON-LD, 13 copy spots), `index.html`
  (6 spots) and `llms.txt` (4). **`Shopify Plus` in the homepage portfolio card stays** — that's a real
  delivered project, not an integration claim.

**Gotcha for next time:** the JSON-LD `FAQPage` answers must stay character-identical to the visible
FAQ paragraphs or Google flags the markup. Verified by parsing the JSON-LD and asserting each
`acceptedAnswer.text` appears verbatim in the stripped page text (all 6 matched).

### Hero BETA pill (2026-08-02)

The hero's `<span class="eyebrow">Profit copilot</span>` was replaced by `.cp-beta` —
"● LoveAds Copilot — **Now in BETA**". The old eyebrow just restated the H1 sitting right under it;
the beta status is actual news. Static (not a link) so it doesn't compete with the "Open Copilot"
CTA a few centimetres below. `/copilot` hero only — on the homepage the Copilot block is a showcase,
and a BETA badge there would fight the consulting message.

Modelled on the announcement pill at oxygenbuilder.com. The premium detail is the highlight sweeping
around the border: a conic-gradient `::before` rotating every 5 s, with an `inset:1px` `::after` fill
over it so only a 1 px ring stays lit. `z-index:0` on the pill keeps both negative-z pseudo-elements
inside its own stacking context — without it they would paint behind the section background.
Under `prefers-reduced-motion` the sweep is replaced by a plain soft-crimson ring.

### Typography (2026-08-03)

Three faces, three jobs: **Archivo 700 at `wdth` 92** for h1/h2, **Geist** for body and UI, **IBM
Plex Mono** for figures and labels. Loaded as one Google Fonts request per page with
`preconnect` + `link` in the head.

**Gotchas worth keeping:**

- **`document.fonts.check()` lies.** It returned `false` for a face that was loaded and `true` in
  cases where the face had not arrived. The reliable test is measurement: render the same string in
  the target family and in its generic fallback and compare widths. Every font claim in this cycle
  was verified that way.
- **Fonts load lazily.** Screenshots taken right after `document.fonts.ready` caught the page before
  a face had downloaded. Force each face with `document.fonts.load('<weight> <size>px "<family>"')`
  before capturing, or the comparison is of the fallback.
- **Discrete weight requests silently clamp.** `wght@300;400;450;500;600;700` cannot render 560.
  Request a range (`wght@400..700`) for real intermediate weights.
- **`.cp h1` beats `.cp-hero-title`.** Class + element out-specifies class alone. Display classes and
  element-level heading rules must not both set weight.
- **Variable axes are expensive.** Archivo's width axis costs 87.9 KB against 34.1 KB without it, for
  a 7.2% narrowing. Measured with `curl` against the `fonts.gstatic.com` URL in the served CSS.

**To audit typography again:** load the page, force-load every face, then read
`getComputedStyle(el).fontFamily` and `.fontWeight` on one element per role, and separately confirm
each family renders by measuring it against its fallback. Checking only the computed style tells you
what the CSS asked for, not what the browser drew.

### Tooling (2026-08-06)

Four of Emil Kowalski's animation skills installed at user scope in `~/.claude/skills/`:
`review-animations`, `improve-animations`, `find-animation-opportunities`, `animation-vocabulary`.
Installed with `npx skills add emilkowalski/skills -s <name> --agent claude-code --global`; note the
CLI needs a **repeated `-s` flag** per skill, a comma-separated list is silently rejected.

**Deliberately not installed:** `emil-design-eng` and `apple-design` overlap Impeccable and
`DESIGN.md`, and two design authorities that can contradict each other are worse than one.
`pick-ui-library` and `prototype` have nothing to act on here — no component library, no build step.

Nothing was written into the repo; the install is user-level only.

**Not yet run.** The obvious first target is `/review-animations` over the four motion systems now on
the site: the pinned POAS scrub, the BETA pill's 5s conic sweep, the hero mock's 4.2s cross-fade, and
the reveal staggers.

### Cookie consent (2026-08-04)

Files: `includes/js/consent.js`, consent CSS appended to both stylesheets, inline Consent Mode
defaults in each page `<head>`. Spec:
`docs/superpowers/specs/2026-08-04-cookie-consent-design.md`.

**The ordering constraint that decides the whole design:** `gtag('consent','default', …)` must
execute before `gtag.js` loads, or Consent Mode is not applied. That is why the defaults are inline
in `<head>` (~300 bytes) while the 165 KB library is injected from `consent.js` after the `load`
event. Reversing those two silently disables the whole mechanism, and nothing visibly breaks.

**Gotcha when testing:** the harness first reported zero hits after Accept, which looked like consent
was blocking measurement. It was the test's own three-second wait — `gtag.js` is injected after
`load`, then has to download and initialise. At six seconds the hit fires and `_ga` cookies appear.
Do not conclude anything about deferred analytics from a short timeout.

**The assertion worth re-running after any change:** clear storage, load, click Decline, reload, and
check `Network.getAllCookies` is still empty. Counting `/g/collect` requests is not the test —
Consent Mode deliberately sends cookieless pings in the denied state, so a hit there is correct.

### Accessibility & SEO audit (2026-08-03)

Lighthouse on live before the work: **Performance 76 · Accessibility 92 · Best Practices 100 ·
SEO 100**, FCP 3.8s, LCP 4.3s, TBT 0ms, **CLS 0**.

Fixed: contrast on `--text-3` (3.09:1 → ≥4.73 on all three grounds), `--on-ink-3` (3.52 → 4.80),
`--cp-muted` (3.16 → 4.87) and `--cp-good` (4.08 → 5.01); a footer `h2→h5` jump and a `h2→h4` jump
in the decision loop; a missing `<main>` landmark on all four pages; `role="tab"` on the eight tab
buttons whose `role="tablist"` parent requires it; `aria-label` on the three calculator range inputs;
and a brand `aria-label` that omitted the visible "Copilot". All four pages now score **100 on
Accessibility, Best Practices and SEO**.

**Two gotchas worth keeping:**

- **CLS was already 0**, despite no image carrying `width`/`height`. The images sit in containers
  with fixed CSS dimensions, so nothing shifted. The attributes were added anyway as protection
  against slow connections and future layout changes — but do not assume missing dimensions means
  measured layout shift. Measure first.
- **Lighthouse contrast audits are unreliable on this page.** `/copilot` scored 96, 96, 100 across
  three identical runs. The failures reported `#f8f8f8 on #ffffff` at 1.06:1 — a colour that is in
  no palette. The elements were inside the hero mock, which cycles three views every 4.2s with an
  opacity transition, so the audit was sampling mid-fade. Resting contrast measures 4.89–9.50:1.
  Before chasing a contrast failure, check whether the element is inside something that animates.

### The POAS scroll moment (2026-08-02) — GSAP ScrollTrigger — **REMOVED 2026-08-10**

> **This no longer exists.** GSAP and ScrollTrigger were deleted on 2026-08-10: 47 KB from a second
> origin, and about 0.7s of extra main-thread work, for one section. Removing them took `/copilot`
> from 78 to 86 on mobile — the single largest gain of that cycle.
>
> The deciding argument was that the effect below was **desktop-only in practice**. On phones the same
> code ran with `pin: false, scrub: false`, which reduces to a staggered fade on viewport entry —
> exactly what the page's own IntersectionObserver already does for every other block. Phones were
> downloading a library to reproduce behaviour that already shipped.
>
> Nothing had to replace it. The stat card's column already carries `class="col-lg-6 reveal"
> style="--d:1"`, so it fades in through the existing observer, and the five count-ups returned to the
> page-wide `cio` observer that `initPoasProof()` used to take them off.
>
> **If you ever bring it back:** load it on desktop only (`matchMedia('(min-width: 900px)')` before
> injecting the scripts), and self-host rather than pulling from jsDelivr — the origin handshake cost
> more than the bytes. The reasoning below is kept because it is still the right argument for *why*
> one section should earn motion; only the implementation was wrong for mobile.

Spec: `docs/superpowers/specs/2026-08-02-copilot-poas-scroll-moment-design.md`.

**Why only one.** All 15 sections used the same `.reveal` fade-up, so nothing read as important.
The fix was not more motion — it was one section that earns it, with the rest kept quiet. The POAS
section carries the product's central argument (ROAS flatters, POAS tells the truth), so sequencing
its five stat rows turns a claim into a demonstration.

**How it works** (`#poasProof`, GSAP 3.13 + ScrollTrigger from jsDelivr, `defer`, ≈36 KB gz):
- Desktop ≥900 px: section pins for `+=140%`, `scrub: 0.4`; rows animate in order, the last two
  (real cost, then POAS) get 1.4× the scroll distance because that's where the argument lands.
- Mobile <900 px: **no pin** — pinning on phones reads as scroll hijack. Same timeline, fired once
  on entry with a fixed duration. A `matchMedia('(min-width:900px)')` change listener rebuilds the
  timeline when the boundary is crossed.
- Count-up reuses the existing `animateCount()` (idempotent via `dataset.done`), called per row from
  the timeline. **The POAS rows are `cio.unobserve()`d** — otherwise the page-wide count-up observer
  fires them on viewport entry, while the rows are still invisible, and the reveal lands on numbers
  that already finished counting.

**The rule that makes this safe: the hidden state is set from JS, never from CSS.** `gsap.set(rows,
{opacity:0})` runs at init, behind a `window.gsap && window.ScrollTrigger` guard and a
`prefers-reduced-motion` check. If the CDN is blocked or GSAP fails, the rows are simply visible —
there is no code path that yields an empty section. Verified with GSAP blocked at the network layer.

**Also fixed here:** the missing `.js` gate (old §10 follow-up). `.cp .reveal{opacity:0}` was
unconditional, so with JS off everything below the hero was invisible — unacceptable once the page
depends on an external animation library. Now `.js .cp .reveal` + an inline `<script>` in `<head>`,
matching `site.css`.

**Gotcha:** GSAP's pin inserts a `pin-spacer` and the nav is `position: sticky` — the classic place
this breaks. Verified the nav stays at `top: 0` throughout the pin.

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
   stable.
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
- **PHP does not run locally** (no `php` on this machine), so `python3 -m http.server` serves `.php` as a
  download. Render the few PHP expressions into a throwaway `.html` at the repo root — the absolute
  `/includes` paths need it there — test that, then delete it.

### Measuring performance (added 2026-08-10, learned the hard way)

- **Local Lighthouse against the live site cannot be compared run to run.** Five runs of the same
  unchanged homepage, minutes apart from this machine: Performance **76, 76, 87, 98, 97**; LCP 2.16s
  to 5.36s. A 22-point spread with nothing changing. A single run is not a baseline, and a later run
  scoring worse proves nothing — a "regression" was reported during the 2026-08-10 cycle that was
  entirely this.
- **Real numbers come from [pagespeed.web.dev](https://pagespeed.web.dev)**, run by the owner. The PSI
  *API* (`pagespeedonline/v5/runPagespeed`) has a shared keyless quota that was exhausted on every
  attempt — do not plan around it.
- **Local Lighthouse is still the right tool for a controlled A/B:** same machine, same minute, one
  variable, two or more runs per arm. Absolute numbers will not match production (localhost has no
  document latency) but the *delta* transfers. This is how the hero LCP fix was measured at −0.95s.
- **The opportunity list is stable even when the score is not.** `render-blocking-resources`,
  `uses-http2`, `unused-javascript` are structural. Read those, not the number.
- **Disable the cache between before/after captures.** Reusing one Chrome instance across both arms
  served a cached stylesheet and produced **487 phantom differences** in a computed-style diff. Send
  `Network.setCacheDisabled {cacheDisabled: true}`, or use a fresh `--user-data-dir` per arm.
- **`getComputedStyle` immediately after toggling a class returns the transition's *starting* value**,
  not its end state. A drawer that moved 170px measured as 0 travel. Await `transitionend` with a
  timeout fallback before snapshotting.
- **A stale system resolver will make a DNS change look like it failed.** After the Cloudflare switch,
  `curl` kept hitting the old origin IP and every check said nothing had applied. Force it:
  `curl --resolve host:443:<ip>`, and query authority directly with `dig @<nameserver>`.
- **Pixel-diffing screenshots needs a control.** `/copilot` showed differences at five of six
  breakpoints after the grid swap; capturing twice from *identical* code reproduced them in the same
  region at the same magnitude (309 vs 311 px at 992). The cause was `cp-beta-sweep`, a 5s infinite
  rotation caught at different phases. Always diff a same-code pair before believing a before/after one.
- **One hypothesis tested and disproved**, recorded so it is not retried: that the infinite
  `cp-beta-sweep` inflates Speed Index. With the animation disabled, Speed Index was identical to two
  decimal places. Live SI was high for network reasons, not animation.

---

## 10. Open follow-ups / ideas

- **Replace mock data with real anonymized screenshots/GIF** from `copilot.loveads.ro` (brief §11) —
  biggest remaining conversion lift on the landing.
- If advanced bots get past the form anti-spam → add **Cloudflare Turnstile** (free, no cookie banner).
- Optional polish: tune hero cycle speed, calculator defaults, tab order.
- **Closed 2026-08-02:** the repo cleanup below settled this. `main.css` and the orphaned assets were
  removed from git; the untracked 2018–2019 files stay on the local machine (git never held them, so
  deleting would be irreversible, and they never deploy).
- ~~Second scroll moment candidate: the **Decision Loop**~~ — moot as of 2026-08-10. The POAS moment
  itself was removed for weight, so there is no pinned moment to dilute. Any future scroll-driven
  effect should be desktop-gated and self-hosted from the start.

**Added 2026-08-10:**

- **`hai-sa-ne-cunoastem.php` is not shippable.** Untracked in the working tree. Its two forms post to
  `action=""` with no handler, so submissions are silently discarded — and simply pointing them at
  `contact.php` would not work either: that handler `silent_ok()`s anything without `_js=1` and
  `_elapsed >= 3000`, so every submission would return success and send nothing. All 17 of its images
  are missing from the repo, and both its font families are gitignored on purpose (`.gitignore:45-46`).
  Do not deploy it without resolving all three.
- **`copilot.loveads.ro` ships no security headers.** Checked 2026-08-10: TLS 1.3, valid Let's Encrypt
  cert, HTTP→HTTPS redirect, TLS 1.0/1.1 rejected — the transport is fine. But
  `strict-transport-security`, `content-security-policy`, `x-content-type-options`, `x-frame-options`,
  `referrer-policy` and `permissions-policy` are all absent. Without frame protection the app can be
  iframed anywhere, which matters for a login screen. Caddy sits in front; a few lines there would fix
  it. `loveads.ro` has no HSTS either, but that one is a Cloudflare toggle now.
- **~80 design-system findings in `copilot.css`** (colours, font sizes, radii outside `DESIGN.md`) plus
  `.cp-hero-grid`'s decorative grid background. All predate this cycle and none were touched. A
  design-system conversation, not a performance one — worth doing with the whole file in view.
- **Remaining performance items are marginal.** Every Lighthouse opportunity now reports 0ms.
  `loveads-logo-dark.png` is 739×160 at 25.6 KB but displays at 139×30 — resizing to 278×60 takes it to
  11.2 KB, worth doing for tidiness rather than for the score. `unminified-css` offers 3 KB.
- **Brotli will not engage** while Apache gzips first; Cloudflare does not recompress. Measured at
  ~1 KB per file on `site.css`. Deliberately dropped — see `CLOUDFLARE-RUNBOOK.md` §4.
- Resolved 2026-08-02: **`/copilot` reveal is now `.js`-gated** (was the long-standing no-JS gap).
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
- `Generalize Shopify wording and add Google Analytics as a data source`
- `Drop the source-to-Copilot arrow onto its own line on phones`
- `Add spec for the POAS scroll moment on /copilot`
- `Add the POAS scroll moment and gate /copilot reveals on .js`
- `Replace the hero eyebrow with a BETA announcement pill`
- `Remove dead code and orphaned assets from the repo`
- `Ignore AI tooling files so they can never reach public_html`
- `Also ignore .impeccable/ detector config`
- `Replace Geist headings with Newsreader and Geist Mono with IBM Plex Mono`
- `Set display type in Archivo instead of Geist`
