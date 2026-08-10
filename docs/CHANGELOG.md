# Changelog — loveads.ro

Reverse-chronological record of what shipped. Operational detail, gotchas and the reasoning behind
each decision live in `WORKLOG.md`; this file is the index of *what changed, when, and why*.

---

## 2026-08-10

### /copilot went from 68 to 86 on mobile, by deleting things

PageSpeed rated the Copilot page **99 on desktop and 68 on mobile**. The same HTML, the same server.
That gap is always network-bound: mobile Lighthouse simulates a slow connection where a request to a
second origin costs DNS, TCP and TLS before a single byte of content arrives. Desktop barely notices;
a phone pays for every one.

The page was making three such requests. None of them survived.

| Removed | Weight | What it was doing |
|---|---|---|
| `bootstrap.bundle.min.js` | 79 KB | nothing — no `data-bs-*` attribute, no component, never referenced |
| `bootstrap-icons.css` + woff2 | 97 KB + a font | drawing 24 glyphs |
| `bootstrap.min.css` | 227 KB (33 KB on the wire) | nine grid classes and two alignment utilities |
| `gsap` + `ScrollTrigger` | 47 KB | one section's scroll animation |

The icons are now inline SVG lifted verbatim from the same package, so the artwork is identical
rather than redrawn, sized in `em` and filled with `currentColor` so every existing font-size and
colour rule kept working untouched. The grid was replaced with about 70 lines carrying Bootstrap
5.3.8's own breakpoints and container widths — reproduced, not reinterpreted — living inside
`copilot.css` so it costs no request.

**The grid swap nearly shipped broken.** `copilot.css` declares only `box-sizing` and
`scroll-behavior`; everything else it assumed came from Bootstrap's Reboot. Without it the body took
the browser's 8px margin and headings reverted to user-agent defaults. Caught by diffing computed
styles at six breakpoints before committing, not by reading the file. The replacement now carries the
slice of Reboot the page actually depends on, and the diff across 375, 576, 768, 992, 1200 and
1440px is **zero differences** — body, containers, every `col-*`, cards, buttons, and the width and
left offset of the first 24 columns.

**GSAP was the single biggest item, at +8 points.** It powered a pinned, scroll-scrubbed sequence in
the POAS section — a real effect on desktop, worth a library. But the same code ran on mobile with
`pin: false, scrub: false`, which reduces to a staggered fade on viewport entry: exactly what the
page's own IntersectionObserver already does for every other block. Phones were downloading 47 KB
from a second origin to reproduce a behaviour that already shipped. Removed entirely at the owner's
call; the stat card's column already carried `class="col-lg-6 reveal"`, so nothing had to be added
back.

### The hero was hiding its own LCP element

Lighthouse named `.cp-hero-sub` as the page's Largest Contentful Paint element. It was a `.reveal`
carrying `--d:2`, so it began at `opacity: 0`, waited 140ms of stagger, then faded for 600ms. LCP
ignores a fully transparent element — the hero was withholding its largest paint from the clock for
up to 740ms.

Above the fold there is nothing to reveal; the reader has not scrolled. Hero reveals now hold opacity
at 1 and animate only the slide, so the text is painted and measured on the first frame while the
entrance still plays. A controlled A/B on localhost measured **LCP 4.04s → 3.09s**. The homepage hero
has never used `reveal`, for the same reason.

### Cloudflare was already there, switched off

The domain had been on Cloudflare nameservers for some time with every record on the grey cloud, so
Cloudflare was serving DNS and nothing else. Turning on the proxy was two toggles, not a migration —
worth **+3 points**, and it fixed the `uses-http2` opportunity that no code change could reach.

Mail was moved to its own unproxied record first and confirmed by a real delivery before anything was
proxied. `MX` pointed at the root domain, so proxying it would have sent mail delivery to Cloudflare,
which does not carry SMTP. The full procedure, the settings that matter, and the rollback are in
`CLOUDFLARE-RUNBOOK.md`.

Brotli was investigated and dropped: Apache gzips first and Cloudflare will not recompress, and the
difference measured about 1 KB per file. `http://` returns 403 with no redirect — long-standing origin
behaviour, not caused by the change, now handled at the edge with *Always Use HTTPS*.

### Motion, on both live pages

A review of the animation code found eight unbounded `transition: all` declarations, two layout-property
animations, keyframes on views that re-trigger every 4.2 seconds, and a site-wide reveal with no
reduced-motion path.

- `.work`'s hover inset moves on `transform` rather than `padding`. It also never animated at all:
  `.work` carries `class="work reveal"`, and `.js .reveal` (0,2,0) has always out-specified `.work`
  (0,1,0), so the padding snapped. Moving the transition to the children is what makes it run.
- `.cp-view` moved from a keyframe to transitions. The hero mock re-runs it every 4.2s, and a keyframe
  restarted from zero on every swap; `ease` is ease-in-out, which held the view back at exactly the
  moment the eye arrives.
- `.cp-nav` no longer transitions `padding`. It is `position: fixed` with `backdrop-filter: blur(14px)`,
  so animating padding made the blur resample a resizing region for 200ms.
- The homepage `.reveal` gained a `prefers-reduced-motion` path — the largest movement on the site had
  none, while `/copilot`'s identical reveal has had one since it shipped.
- Twenty-five hover rules across both stylesheets are now gated behind `(hover: hover) and (pointer: fine)`.
  On touch the tap fires a synthetic hover and the state sticks after the finger lifts.
- The hero mock pauses on `focusin`, not only on hover. It cycles every 4200ms and a keyboard user had
  the view swap out from under them with no way to hold it.

### A note on how this was measured

Local Lighthouse runs against the live site proved useless for comparison: five runs of the same
unchanged homepage minutes apart scored **76, 76, 87, 98, 97**, with LCP between 2.16s and 5.36s. A
"regression" was reported during this work that turned out to be nothing but that spread. Every score
quoted above comes from pagespeed.web.dev; local runs were used only for controlled A/B with one
variable and both arms measured in the same minute.

One hypothesis was tested and disproved: that the infinite `cp-beta-sweep` rotation was inflating
Speed Index. With the animation disabled, Speed Index was identical to two decimal places.

**Where it landed:** `/copilot` 68 → **86** on mobile, homepage **97**, desktop 99. No third-party
resource remains in the critical path on either page; `gtag.js` is the only one left anywhere, and it
is deferred. Every remaining Lighthouse opportunity reports 0ms.

---

## 2026-08-04

### Cookie consent bar with Google Consent Mode v2

GA4 went live the day before and set analytics cookies on every page, while the privacy policy named
consent as the legal basis and nothing on the site asked for it. That gap is now closed.

A non-blocking bar sits at the bottom on the dark ground the design system reserves for full-bleed
punctuation, so it reads as the system speaking rather than as more content. Two buttons, identical
in size and shape — GDPR requires refusing to be as easy as accepting, and a quiet "decline" link
beside a prominent "accept" is what regulators penalise. No category toggles: exactly one
non-essential category exists, and anything more would be compliance theatre.

**Consent Mode v2 ordering:** the defaults (`denied` across `ad_storage`, `analytics_storage`,
`ad_user_data`, `ad_personalization`) are set inline in `<head>`, because they must run before
`gtag.js` or Consent Mode does not apply at all. The library itself is now injected after the `load`
event, which takes 165 KB off the critical path — at the stated cost of not counting visitors who
leave within about two seconds.

The decision lives in `localStorage`, not a cookie; setting a cookie to record a refusal of cookies
is self-defeating. It is re-asked after six months. A "Cookie settings" link in every footer reopens
the bar, because GDPR grants a right to withdraw consent and the policy already promises it.

On `/copilot` the sticky CTA now yields while the bar is up — they occupied the same strip, and it is
the right hierarchy anyway: do not ask someone to sign up before telling them what you collect.

**Verified end to end:** no cookies before a choice; **no cookies after Decline, even across a
reload**; `_ga` and `_ga_1BF1ET6PWQ` only after Accept. Contrast on the dark bar measures 10.5:1 for
body text and 5.2:1 on the accept button. No horizontal overflow at 390px.

---

## 2026-08-03

### The site is no longer set entirely in Geist

Geist is Vercel's typeface and by now the default face of AI-generated landing pages. It read as
competent and anonymous — the opposite of what a consultancy selling judgment should look like.

The system is now three faces with three jobs, and no overlap:

| Role | Face | Why |
|---|---|---|
| Display (h1, h2) | **Archivo 700, `wdth` 92** | Industrial grotesque. The 8% narrowing is what stops it reading as a generic bold sans. |
| Body and UI | **Geist** | Unchanged, and deliberately unremarkable — the voice lives in the display face. |
| Figures and labels | **IBM Plex Mono** | Institutional rather than code-editor; keeps numbers in a column. |

### Three defects found while doing it

- **A font weight that never existed.** Headings declared `font-weight: 560`, but the fonts were
  requested as discrete instances (`300;400;450;500;600;700`), so 540, 560 and 580 all rendered as
  600. Measured, not assumed: 732.94px for each of them against 709.27px at 500. Fonts are now
  requested as variable ranges, so intermediate weights are real for the first time.
- **`@import` was blocking rendering.** `site.css` loaded fonts with a CSS `@import`, which
  serialises the fetch behind the stylesheet. `index.html`, `terms.html` and `privacy-policy.html`
  now use `<link rel="preconnect">` + `<link>` in the head, as `/copilot` already did.
- **A specificity conflict on `/copilot`.** `.cp h1` (class + element) out-specified
  `.cp-hero-title` (class only) and forced a weight onto the display face. The heading block is
  split: h1/h2 take the display face, h3–h5 stay body-sans.

### Directions tried and rejected

Recorded so nobody re-litigates them: **Newsreader** (a reading serif — rejected as "not high
tech"), **Martian Mono** as a display face, **Bricolage Grotesque**, **Host Grotesk** (too close to
Geist, so it did not solve the problem), and **Archivo Narrow** (18 KB and genuinely narrower, but
rejected on looks). **DM Sans** was considered as a Geist replacement and rejected: it is also a
saturated default, it is twice the weight, and body text should not have a personality that competes
with the display face.

### Impeccable installed as a Claude Code plugin

`pbakaus/impeccable` added as a second marketplace and installed at user scope, alongside
superpowers. Two artefacts were generated and live **locally only** — `PRODUCT.md` records durable
product truth, `DESIGN.md` plus `.impeccable/design.json` record the visual system under the North
Star **"The Instrument"**.

Both are gitignored on purpose. Deploy is `rsync --delete` from the repo root, so anything committed
there is publicly served. `.gitignore` now also covers `.claude/`, `.cursor/`, `.codex/`, `.grok/`,
`.impeccable/` and `node_modules/`. `/PRODUCT.md` and `/DESIGN.md` are anchored to the root, so a
copy under `docs/` stays committable if the design context is ever worth versioning.

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
