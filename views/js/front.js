/* Websource Blog : carousel d'articles (défilement natif + flèches + points + défilement auto) */
(function () {
  'use strict';
  function ready(fn) { document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn); }
  ready(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    [].forEach.call(document.querySelectorAll('[data-wsb-carousel]'), function (root) {
      var track = root.querySelector('.wsb-track');
      if (!track || track.children.length < 2) return;
      function mk(cls, label, txt) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'wsb-arrow ' + cls; b.setAttribute('aria-label', label); b.textContent = txt;
        root.appendChild(b); return b;
      }
      var prev = mk('prev', 'Articles précédents', '‹');
      var next = mk('next', 'Articles suivants', '›');
      var dots = document.createElement('div'); dots.className = 'wsb-dots'; root.appendChild(dots);
      function step() { var a = track.children[0], b = track.children[1]; return b.offsetLeft - a.offsetLeft; }
      function pages() { return Math.max(1, Math.ceil((track.scrollWidth - track.clientWidth) / step()) + 1); }
      function buildDots() {
        dots.innerHTML = ''; var n = pages(); if (n < 2) return;
        for (var i = 0; i < n; i++) (function (i) {
          var d = document.createElement('button'); d.type = 'button'; d.setAttribute('aria-label', 'Aller à la position ' + (i + 1));
          d.addEventListener('click', function () { track.scrollTo({ left: i * step() }); }); dots.appendChild(d);
        })(i);
      }
      function sync() {
        var max = track.scrollWidth - track.clientWidth;
        prev.disabled = track.scrollLeft <= 2; next.disabled = track.scrollLeft >= max - 2;
        var idx = Math.round(track.scrollLeft / step());
        [].forEach.call(dots.children, function (d, i) { d.classList.toggle('on', i === idx); });
      }
      prev.addEventListener('click', function () { track.scrollBy({ left: -step() }); });
      next.addEventListener('click', function () { track.scrollBy({ left: step() }); });
      track.addEventListener('scroll', function () { window.requestAnimationFrame(sync); }, { passive: true });
      window.addEventListener('resize', function () { buildDots(); sync(); });
      buildDots(); sync();

      if (reduce || root.getAttribute('data-autoplay') !== '1') return;
      var paused = false, timer = null;
      ['mouseenter', 'focusin', 'touchstart'].forEach(function (e) { root.addEventListener(e, function () { paused = true; }, { passive: true }); });
      ['mouseleave', 'focusout', 'touchend'].forEach(function (e) { root.addEventListener(e, function () { paused = false; }, { passive: true }); });
      function tick() {
        if (paused || document.hidden) return;
        var max = track.scrollWidth - track.clientWidth; if (max <= 2) return;
        if (track.scrollLeft >= max - 2) track.scrollTo({ left: 0 }); else track.scrollBy({ left: step() });
      }
      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { en.forEach(function (x) { clearInterval(timer); if (x.isIntersecting) timer = setInterval(tick, 4500); }); }, { threshold: 0.3 }).observe(root);
      } else { timer = setInterval(tick, 4500); }
    });
  });
})();
