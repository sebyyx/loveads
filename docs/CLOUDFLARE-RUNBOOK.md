# Turning Cloudflare on for loveads.ro

_Measured 2026-08-09. Verify the "current state" section still holds before following this —
DNS drifts, and a stale runbook is worse than none._

The nameserver migration everyone dreads **is already done**. The domain has been on Cloudflare
nameservers for some time, but every record is on the grey cloud, so Cloudflare is serving DNS and
nothing else. Traffic goes straight to the origin. Switching on the performance benefit is a toggle,
not a migration.

**But there is one way to break the company's email doing it, and this configuration is exactly the
vulnerable one.** Read section 2 before touching anything.

---

## 1. Current state, as measured

```
loveads.ro          NS    jessica.ns.cloudflare.com, anirban.ns.cloudflare.com
loveads.ro          A     88.99.253.220        grey cloud  (Hetzner, Germany)
www.loveads.ro      A     88.99.253.220        grey cloud
copilot.loveads.ro  A     167.233.61.148       grey cloud  (the app — a different server)
mail.loveads.ro     CNAME loveads.ro           grey cloud
loveads.ro          MX    0 loveads.ro
loveads.ro          TXT   v=spf1 +a +mx +ip4:88.99.253.220 ~all
```

Response headers from `https://loveads.ro/`: `HTTP/1.1`, `Server: Apache`, no `cf-ray`, gzip (not
Brotli). All three confirm the proxy is off.

Check it yourself:

```sh
dig +short NS loveads.ro                  # cloudflare nameservers
curl -sI https://loveads.ro/ | grep -i "cf-ray\|server"   # cf-ray absent == not proxied
```

**Why it is DNS-only is not recorded anywhere.** If somebody set it this way deliberately — the
origin IP needing to stay visible for a monitor, a mail service, an API allow-list — that is a
decision to respect, not to undo. Find out before proceeding.

---

## 2. The mail trap — do this part first

`MX` points at `loveads.ro`, and `mail.loveads.ro` is a CNAME to `loveads.ro`. Both follow the root
record.

Orange-cloud the root and `loveads.ro` starts resolving to Cloudflare's proxy IPs. Sending servers
read the MX, resolve `loveads.ro`, and try to deliver to Cloudflare — **which does not proxy SMTP on
port 25**. Incoming mail stops. There is no error you would notice from the outside; mail simply
fails to arrive.

Move mail off the record you are about to proxy, and only then proxy it.

### Step 1 — give mail its own unproxied record

In the Cloudflare DNS panel:

- **Delete** `mail` (the CNAME to `loveads.ro`)
- **Add** `mail` as an **A** record → `88.99.253.220`, proxy status **DNS only (grey)**

The grey cloud here is not optional. A proxied mail record breaks the same way.

### Step 2 — repoint MX

- Change `MX` for `loveads.ro` from `loveads.ro` to `mail.loveads.ro`, priority `0`

### Step 3 — verify before going further

TTLs measured at ~35s, so this propagates in about a minute.

```sh
dig +short MX loveads.ro                  # expect: 0 mail.loveads.ro.
dig +short A mail.loveads.ro              # expect: 88.99.253.220
```

Then **send a real email to an address on the domain and confirm it arrives.** Do not skip this.
Everything after this point assumes mail is off the root record.

---

## 3. Turn the proxy on

Only after section 2 verifies clean:

- `loveads.ro` → proxy **Proxied (orange)**
- `www.loveads.ro` → proxy **Proxied (orange)**

Leave `copilot.loveads.ro` grey. It is the application on a separate server; edge caching buys it
nothing and could serve a stale app shell.

Verify:

```sh
curl -sI https://loveads.ro/ | grep -i "cf-ray\|server"   # expect cf-ray present, server: cloudflare
curl -sI --http2 https://loveads.ro/ | head -1            # expect HTTP/2 200
curl -sI -H "Accept-Encoding: br" https://loveads.ro/site.css | grep -i content-encoding   # expect br
```

---

## 4. Settings that matter

**SSL/TLS → Overview → Full (strict).**
The origin already serves a valid certificate, so strict works. `Flexible` would make Cloudflare talk
to the origin over plain HTTP and can produce redirect loops.

**Caching → Cache Rules → bypass `/.well-known/*`.**
cPanel's AutoSSL renews by answering an HTTP challenge under `/.well-known/acme-challenge/`. If
Cloudflare caches or intercepts that path, renewal can fail silently and the origin certificate
expires months later, which is a miserable thing to debug. Add the bypass before you need it.

**Speed → Brotli**, **Network → HTTP/2**, **HTTP/3 (QUIC)** — on by default on the free plan.
Confirm rather than assume.

**Do not enable Auto Minify / Rocket Loader.** Rocket Loader reorders script execution, and this site
has an inline Consent Mode block whose ordering is load-bearing: the consent defaults must run before
`gtag.js` or Consent Mode silently does nothing.

---

## 5. Fix SPF afterwards

Current record:

```
v=spf1 +a +mx +ip4:88.99.253.220 ~all
```

`+a` authorises whatever `loveads.ro` resolves to. Once proxied, that is Cloudflare's IP range — so
the record would declare Cloudflare's entire edge as a legitimate sender for the domain. Wrong, and
needlessly permissive.

Change to:

```
v=spf1 +mx +ip4:88.99.253.220 ~all
```

`+mx` now resolves to `mail.loveads.ro`, which stays grey, so it keeps pointing at the real mail
host.

---

## 6. What changes in how the site is deployed

**Deploy gains a step.** `site.css` is served with `max-age=86400`, so once Cloudflare caches it at
the edge, a deploy can leave visitors on the old stylesheet for up to a day.

The deploy sequence becomes:

1. Push to `main`
2. cPanel → *Update from Remote* → *Deploy HEAD Commit*
3. **Cloudflare → Caching → Purge Everything**

Worth adding to `CLAUDE.md` once this is live, or the next person will ship a change and swear it did
not deploy.

`contact.php` is safe — Cloudflare does not cache POST requests.

---

## 7. What this is expected to buy

The PageSpeed run on 2026-08-09 listed two remaining opportunities at ~290ms each: `uses-http2`
(12 requests not multiplexed) and `render-blocking-resources`. Both are consequences of HTTP/1.1
having no multiplexing — the browser queues requests instead of running them in parallel. Cloudflare
fixes the protocol, which is the only lever left; every third-party resource has already been removed
from the critical path in code.

It should help the homepage as much as `/copilot`, since both are served from the same origin.

**Measure with [pagespeed.web.dev](https://pagespeed.web.dev), not with a local Lighthouse run.**
Local runs from a laptop against this host swing 22 points and 3.2 seconds of LCP on an unchanged
page, which is far wider than the effect being measured.

---

## 8. Rolling back

Flip the two records back to grey. DNS TTLs are ~35s, so recovery is about a minute. If mail was
already moved to `mail.loveads.ro` in section 2, leave it there — that change is correct and worth
keeping regardless of whether the proxy stays on.
