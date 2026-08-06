# loveads.ro — working notes for Claude

Read this first. `docs/CHANGELOG.md` says what shipped and when; `docs/WORKLOG.md` carries the
gotchas and the reasoning. This file is only what you need before touching anything.

## What the site is

Static HTML/CSS/JS plus one PHP form handler, for **Love Ads Marketing SRL**.

- `index.html` — homepage. Product-strategy consulting, with a LoveAds Copilot showcase section.
- `copilot/index.html` — the Copilot product landing page.
- `terms.html`, `privacy-policy.html` — legal.
- `contact.php` — contact form, sends via `mail()`, four silent anti-spam layers.

Two audiences of **equal standing**, deliberately on separate surfaces: founders buying consulting
(homepage) and store owners buying Copilot (`/copilot`). Do not quietly turn one into a funnel for
the other.

## Constraints that bind every change

- **No build step. No framework. No bundler.** Anything you add must work as plain files.
- **No SSH.** Ports 22 and 2222 are refused by the host.
- **Deploy is manual.** Claude commits and pushes to `main`; the owner then clicks *Update from
  Remote* → *Deploy HEAD Commit* in cPanel. A push does **not** deploy.
- **`rsync --delete` from the repo root.** Excluded: `.git`, `.cpanel.yml`, `config.php`, `docs`,
  `NOTES.md`, `CLAUDE.md`. **Everything else tracked at the root is publicly served.** Before
  committing a new file at the root, ask whether the world should see it.
- Apache, PHP 8.3, **HTTP/1.1** — no multiplexing, so one fewer request beats a little duplication.
  This is why `@font-face` blocks are repeated in both stylesheets rather than split into a file.
- **Never `git add` a whole directory.** `git add includes/fonts/` once swept nine untracked 2018
  webfonts into a commit and published 496 KB of dead files. Stage named paths, then read
  `git status` before committing.

## Current stack

- **Type:** Archivo 700 at `wdth` 92 (display), Geist (body/UI), IBM Plex Mono (figures/labels).
  All three **self-hosted** from `includes/fonts/` — never reintroduce a Google Fonts link, it sends
  visitor IPs to Google and costs two DNS+TLS handshakes.
- **Analytics:** GA4 `G-1BF1ET6PWQ` with **Consent Mode v2**. Defaults are set inline in each
  `<head>`; `gtag.js` is injected from `includes/js/consent.js` after `load`.
  **The order is load-bearing** — defaults must run before the library or Consent Mode silently does
  nothing.
- **Motion:** GSAP + ScrollTrigger from jsDelivr on `/copilot` only.

## Design authority

`DESIGN.md` and `PRODUCT.md` sit at the root and are **gitignored** — local only, so they cannot be
published. `DESIGN.md` is normative: North Star "The Instrument", the token set, and eight named
rules. If a skill or a suggestion conflicts with it, the document wins.

Impeccable (`/impeccable`) is installed as a user-level plugin. Emil Kowalski's four animation skills
are in `~/.claude/skills/`.

## How work is verified here

The house standard is **measure, don't assume**. Claims in commit messages are expected to be backed
by a number someone can re-derive. Specifically:

- `document.fonts.check()` is unreliable — it has returned false for loaded faces and true for
  absent ones. Measure a string against its generic fallback instead.
- Fonts load lazily. Force each face with `document.fonts.load(...)` before screenshotting, or the
  capture races the download.
- Lighthouse's contrast audit is flaky on `/copilot` because the hero mock cross-fades every 4.2s;
  three identical runs scored 96, 96, 100. Check whether a flagged element sits inside something
  that animates before chasing it.
- CLS is 0 and was 0 before image dimensions were added. Do not assume a missing attribute means a
  measured problem.
- For consent, the test is that `Network.getAllCookies` stays empty after Decline — **not** the count
  of `/g/collect` requests, which Consent Mode deliberately still sends in the denied state.

## Live baseline (2026-08-04, Lighthouse mobile)

Performance 85–86 · Accessibility 100 · Best Practices 100 · SEO 100.
LCP 4.1 s is the only weak metric and the only thing holding Performance down.

## Open items

- `Organization` JSON-LD has no `sameAs` — the site links to no social profile, and inventing one is
  worse than omitting it. Needs a real URL from the owner.
- Cloudflare would bring HTTP/2, a CDN and Brotli, none of which can be fixed from the code. The
  owner has not decided.
- The site is English-only with `lang="en"`; a Romanian version is possible and not ruled out. Avoid
  choices that would make a later translation harder.
