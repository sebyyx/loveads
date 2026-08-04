/* Cookie consent — Google Consent Mode v2.
 *
 * The consent defaults are already set inline in each page's <head>; they have to run
 * before gtag.js or Consent Mode does not apply. This file owns everything after that:
 * the bar, the stored decision, and the deferred injection of the library itself.
 *
 * gtag.js is 165 KB. Injecting it after the load event keeps it off the critical path,
 * at the cost of not counting visitors who leave in the first couple of seconds.
 */
(function () {
  'use strict';

  var KEY = 'loveads_consent';
  var SIX_MONTHS = 15552000000;
  var GA_ID = 'G-1BF1ET6PWQ';

  function read() {
    try {
      var c = JSON.parse(localStorage.getItem(KEY) || 'null');
      if (!c || !c.at) return null;
      return (Date.now() - new Date(c.at).getTime()) < SIX_MONTHS ? c : null;
    } catch (e) { return null; }
  }

  function write(granted) {
    try {
      localStorage.setItem(KEY, JSON.stringify({ granted: granted, at: new Date().toISOString() }));
    } catch (e) {}
  }

  function grant() {
    if (typeof window.gtag !== 'function') return;
    window.gtag('consent', 'update', {
      ad_storage: 'granted', analytics_storage: 'granted',
      ad_user_data: 'granted', ad_personalization: 'granted'
    });
  }

  var libLoaded = false;
  function loadGtag() {
    if (libLoaded) return;
    libLoaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(s);
  }

  // ---- the bar ----------------------------------------------------------------

  var bar = null;

  function build() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.className = 'consent';
    bar.setAttribute('role', 'dialog');
    bar.setAttribute('aria-label', 'Cookie consent');
    bar.innerHTML =
      '<div class="consent-in">' +
        '<p class="consent-txt"><b>We use Google Analytics to see which pages people read.</b> ' +
        "That's it — no ads, no profiles, nothing sold on. " +
        '<a href="/privacy-policy.html">Privacy policy</a></p>' +
        '<div class="consent-btns">' +
          '<button type="button" class="consent-btn consent-no">Decline</button>' +
          '<button type="button" class="consent-btn consent-yes">Accept</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(bar);
    bar.querySelector('.consent-yes').addEventListener('click', function () { decide(true); });
    bar.querySelector('.consent-no').addEventListener('click', function () { decide(false); });
    return bar;
  }

  function show() {
    build();
    // The class also hides /copilot's sticky CTA, which would otherwise sit on top of this.
    document.body.classList.add('consent-open');
    requestAnimationFrame(function () { bar.classList.add('is-in'); });
  }

  function hide() {
    if (!bar) return;
    bar.classList.remove('is-in');
    document.body.classList.remove('consent-open');
    var done = function () { if (bar && bar.parentNode) bar.parentNode.removeChild(bar); bar = null; };
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) done();
    else setTimeout(done, 320);
  }

  function decide(granted) {
    write(granted);
    if (granted) grant();
    hide();
  }

  // ---- boot -------------------------------------------------------------------

  function boot() {
    var prior = read();
    // Denied is the default, so the library can load either way: in the denied state it
    // sets no cookies and sends only the cookieless pings Consent Mode is built around.
    loadGtag();
    if (!prior) show();
  }

  if (document.readyState === 'complete') boot();
  else window.addEventListener('load', boot);

  // Footer "Cookie settings" reopens the bar — GDPR grants a right to withdraw consent,
  // and the privacy policy already promises it.
  window.loveadsConsent = {
    reopen: function () { show(); },
    state: function () { var c = read(); return c ? (c.granted ? 'granted' : 'denied') : 'unset'; }
  };
  document.addEventListener('click', function (e) {
    var t = e.target.closest && e.target.closest('[data-consent-reopen]');
    if (t) { e.preventDefault(); show(); }
  });
})();
