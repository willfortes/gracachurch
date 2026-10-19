/* Graça Church · app.js (sem dependências). Tudo aqui é melhoria: o site funciona sem JavaScript. */
(function () {
  'use strict';
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };
  var T = {};
  try { T = JSON.parse(($('#i18n') || {}).textContent || '{}'); } catch (e) {}
  var html = document.documentElement;
  var store = {
    get: function (k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  };
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isAndroid = /Android/.test(ua);
  var isMac = !isIOS && /Macintosh/.test(ua);
  var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  var platform = isIOS ? 'ios' : isAndroid ? 'android' : isMac ? 'mac' : 'desktop';
  var pushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  var vapidKey = ($('meta[name="vapid-key"]') || {}).content || '';
  var conn = navigator.connection || {};
  var slowNet = !!conn.saveData || /(^|-)(2g|3g)$/.test(conn.effectiveType || '');
  var calm = function () { return html.classList.contains('motion') || window.matchMedia('(prefers-reduced-motion: reduce)').matches; };

  /* ---------- Service worker ---------- */
  var swReady = null;
  if ('serviceWorker' in navigator) {
    swReady = navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function () { return navigator.serviceWorker.ready; }).catch(function () { return null; });
  }

  /* ---------- Topo: transparente no herói, sólido ao rolar ---------- */
  var top = $('[data-top]');
  if (top && $('.hero')) {
    var onScroll = function () {
      var s = window.scrollY > 40;
      top.classList.toggle('solid', s);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Menus (idioma e acessibilidade) fecham ao clicar fora ou com Esc ---------- */
  document.addEventListener('click', function (e) {
    $$('details[data-close-outside][open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    $$('details[data-close-outside][open]').forEach(function (d) { d.open = false; d.querySelector('summary').focus(); });
  });
  $$('[data-lang]').forEach(function (a) {
    a.addEventListener('click', function () { document.cookie = 'gc_lang=' + a.getAttribute('data-lang') + ';path=/;max-age=31536000;samesite=lax'; });
  });

  /* ---------- Acessibilidade (salva em cookie: o servidor já entrega a página ajustada) ---------- */
  var A11Y = ['t-1', 't-2', 't-3', 'contrast', 'motion', 'links', 'spacing'];
  function readPrefs() { return A11Y.filter(function (c) { return html.classList.contains(c); }); }
  function savePrefs(list) {
    A11Y.forEach(function (c) { html.classList.toggle(c, list.indexOf(c) !== -1); });
    document.cookie = 'gc_a11y=' + list.join(',') + ';path=/;max-age=31536000;samesite=lax';
    syncA11y();
  }
  function syncA11y() {
    var p = readPrefs();
    $$('[data-size]').forEach(function (b) {
      var s = b.getAttribute('data-size');
      b.setAttribute('aria-pressed', (s ? p.indexOf(s) !== -1 : !p.some(function (x) { return /^t-/.test(x); })) ? 'true' : 'false');
    });
    $$('[data-pref]').forEach(function (b) { b.setAttribute('aria-pressed', p.indexOf(b.getAttribute('data-pref')) !== -1 ? 'true' : 'false'); });
  }
  syncA11y();
  $$('[data-size]').forEach(function (b) {
    b.addEventListener('click', function () {
      var list = readPrefs().filter(function (x) { return !/^t-/.test(x); });
      if (b.getAttribute('data-size')) list.push(b.getAttribute('data-size'));
      savePrefs(list);
    });
  });
  $$('[data-pref]').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.getAttribute('data-pref'), list = readPrefs();
      list = list.indexOf(k) === -1 ? list.concat(k) : list.filter(function (x) { return x !== k; });
      savePrefs(list);
      if (k === 'motion') startEmbers();
    });
  });
  var reset = $('[data-a11y-reset]');
  if (reset) reset.addEventListener('click', function () { savePrefs([]); });

  // Libras (VLibras, do Governo Federal): carrega só quando a pessoa pede
  var libras = $('[data-libras]');
  if (libras) libras.addEventListener('click', function () {
    var box = $('[data-vlibras]');
    if (!box) return;
    box.hidden = false;
    if (window.VLibras) return;
    libras.disabled = true;
    var s = document.createElement('script');
    s.src = 'https://vlibras.gov.br/app/vlibras-plugin.js';
    s.onload = function () {
      new window.VLibras.Widget('https://vlibras.gov.br/app');
      window.dispatchEvent(new Event('load'));
      setTimeout(function () { var btn = $('[vw-access-button]'); if (btn) btn.click(); libras.disabled = false; }, 1200);
    };
    s.onerror = function () { libras.disabled = false; };
    document.body.appendChild(s);
  });

  /* ---------- Brasas subindo no herói ---------- */
  var embersCanvas = $('[data-embers]'), embersOn = false;
  function startEmbers() {
    if (!embersCanvas || embersOn || calm() || slowNet || html.classList.contains('contrast')) return;
    var ctx = embersCanvas.getContext('2d');
    if (!ctx) return;
    embersOn = true;
    var dpr = Math.min(window.devicePixelRatio || 1, 2), W = 0, H = 0, parts = [], visible = true, raf = 0;
    function size() {
      W = embersCanvas.clientWidth; H = embersCanvas.clientHeight;
      embersCanvas.width = W * dpr; embersCanvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    function spawn(initial) {
      return {
        x: Math.random() * W, y: initial ? Math.random() * H : H + 10,
        r: Math.random() * 2.2 + .6, vy: Math.random() * .6 + .25, vx: (Math.random() - .5) * .3,
        life: 0, max: Math.random() * 500 + 300, hue: Math.random() < .7 ? 28 : 8, sway: Math.random() * 6.28
      };
    }
    size();
    var count = W < 700 ? 34 : 70;
    for (var i = 0; i < count; i++) parts.push(spawn(true));
    function frame() {
      if (!visible || calm()) { raf = 0; embersOn = !calm(); if (calm()) ctx.clearRect(0, 0, W, H); return; }
      ctx.clearRect(0, 0, W, H);
      ctx.globalCompositeOperation = 'lighter';
      for (var i = 0; i < parts.length; i++) {
        var p = parts[i];
        p.life++; p.sway += .02;
        p.y -= p.vy; p.x += p.vx + Math.sin(p.sway) * .25;
        var a = Math.sin(Math.PI * Math.min(1, p.life / p.max)) * .9;
        if (p.y < -10 || p.life > p.max) { parts[i] = spawn(false); continue; }
        var g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
        g.addColorStop(0, 'hsla(' + p.hue + ',100%,70%,' + a + ')');
        g.addColorStop(1, 'hsla(' + p.hue + ',100%,50%,0)');
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 4, 0, 6.283); ctx.fill();
      }
      raf = requestAnimationFrame(frame);
    }
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) {
        visible = en[0].isIntersecting;
        if (visible && !raf) raf = requestAnimationFrame(frame);
      }).observe(embersCanvas);
    }
    document.addEventListener('visibilitychange', function () {
      visible = !document.hidden;
      if (visible && !raf) raf = requestAnimationFrame(frame);
    });
    window.addEventListener('resize', size);
    raf = requestAnimationFrame(frame);
  }
  startEmbers();

  /* ---------- Instalar o app ---------- */
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    $$('[data-install]').forEach(function (b) { b.hidden = false; });
    $$('[data-install-help]').forEach(function (b) { b.hidden = true; });
  });
  window.addEventListener('appinstalled', function () { $$('[data-install]').forEach(function (b) { b.hidden = true; }); });
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-install]');
    if (!btn || !deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.finally(function () { deferredPrompt = null; btn.hidden = true; });
  });
  if (standalone) $$('[data-install-help]').forEach(function (b) { b.hidden = true; });
  $$('[data-platform="' + platform + '"]').forEach(function (el) { el.classList.add('mine'); });

  /* ---------- Avisos (push) ---------- */
  var sheet = $('#push-sheet');
  function b64ToBytes(b64) {
    var raw = atob((b64 + '='.repeat((4 - b64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function getSubscription() {
    if (!pushSupported || !swReady) return Promise.resolve(null);
    return swReady.then(function (reg) { return reg ? reg.pushManager.getSubscription() : null; });
  }
  function post(url, data) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data), credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'erro'); return j; }); });
  }
  function status(el, type, text) {
    if (!el) return;
    el.hidden = !text;
    el.className = 'status ' + type;
    el.textContent = text || '';
  }
  function savedTopics() { return store.get('gc-topics') || null; }
  function fillSheet(preGroup) {
    var topics = savedTopics(), iosNeedsInstall = isIOS && !standalone;
    $('[data-push-ios]', sheet).hidden = !iosNeedsInstall;
    $('[data-push-unsupported]', sheet).hidden = pushSupported || iosNeedsInstall;
    $('[data-push-denied]', sheet).hidden = !(pushSupported && Notification.permission === 'denied');
    $('[data-push-save]', sheet).disabled = !pushSupported;
    status($('[data-push-status]', sheet), '', '');
    var sel = $('[data-push-group]', sheet);
    if (topics) {
      $$('input[name="topic"]', sheet).forEach(function (c) { c.checked = topics.indexOf(c.value) !== -1; });
      var g = topics.filter(function (t) { return t.indexOf('grupo:') === 0; })[0];
      if (sel) sel.value = g ? g.slice(6) : '';
    }
    if (preGroup && sel) sel.value = String(preGroup);
    getSubscription().then(function (sub) { $('[data-push-off]', sheet).hidden = !sub; });
  }
  function openSheet(preGroup) {
    if (!sheet || typeof sheet.showModal !== 'function') { location.href = (T.lang === 'pt' ? '' : '/' + T.lang) + '/' + (T.lang === 'en' ? 'alerts' : 'avisos'); return; }
    fillSheet(preGroup);
    sheet.showModal();
  }
  document.addEventListener('click', function (e) {
    var open = e.target.closest('[data-push-open]');
    if (open) { e.preventDefault(); openSheet(); return; }
    var follow = e.target.closest('[data-group-follow]');
    if (follow) { e.preventDefault(); openSheet(follow.getAttribute('data-group-follow')); }
  });
  if (sheet) {
    $('[data-push-close]', sheet).addEventListener('click', function () { sheet.close(); });
    sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
    $('[data-push-save]', sheet).addEventListener('click', function () {
      var btn = this, st = $('[data-push-status]', sheet);
      var topics = $$('input[name="topic"]:checked', sheet).map(function (c) { return c.value; });
      var sel = $('[data-push-group]', sheet);
      if (sel && sel.value) topics.push('grupo:' + sel.value);
      if (!topics.length) { status(st, 'err', T.pick); return; }
      if (!vapidKey) { status(st, 'err', T.noKey); return; }
      btn.disabled = true;
      status(st, 'info', T.activating);
      Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') throw new Error('denied');
        return swReady;
      }).then(function (reg) {
        if (!reg) throw new Error('sw');
        return reg.pushManager.getSubscription().then(function (sub) {
          return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(vapidKey) });
        });
      }).then(function (sub) {
        return post('/api/push/subscribe', { subscription: sub.toJSON(), topics: topics, platform: platform, lang: T.lang });
      }).then(function (res) {
        store.set('gc-topics', res.topics);
        status(st, 'ok', T.ready);
        $('[data-push-off]', sheet).hidden = false;
        refreshState();
        setTimeout(function () { if (sheet.open) sheet.close(); }, 2400);
      }).catch(function (err) {
        if (err && err.message === 'denied') { $('[data-push-denied]', sheet).hidden = false; status(st, 'err', T.denied); }
        else status(st, 'err', T.failed);
      }).finally(function () { btn.disabled = false; });
    });
    $('[data-push-off]', sheet).addEventListener('click', function () {
      var st = $('[data-push-status]', sheet);
      getSubscription().then(function (sub) {
        if (!sub) return;
        var endpoint = sub.endpoint;
        return sub.unsubscribe().then(function () { return post('/api/push/unsubscribe', { endpoint: endpoint }); });
      }).then(function () {
        store.set('gc-topics', null);
        status(st, 'ok', T.off);
        $('[data-push-off]', sheet).hidden = true;
        refreshState();
      }).catch(function () { status(st, 'err', T.offFail); });
    });
  }
  function refreshState() {
    var el = $('[data-push-state]');
    if (!el) return;
    getSubscription().then(function (sub) {
      var t = savedTopics(), names = T.topics || {};
      if (sub && t) status(el, 'ok', T.on + ' ' + t.map(function (x) { return names[x] || (x.indexOf('grupo:') === 0 ? names.grupo : x); }).join(', ') + '.');
      else if (isIOS && !standalone) status(el, 'info', T.iosFirst);
      else if (!pushSupported) status(el, 'err', T.unsupported);
      else status(el, '', '');
    });
  }
  refreshState();

  /* ---------- Janela "Fale conosco" ---------- */
  var contact = $('#contact-sheet');
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-contact-open]');
    if (!o || !contact || typeof contact.showModal !== 'function') return;
    e.preventDefault();
    contact.showModal();
  });
  if (contact) {
    $('[data-contact-close]', contact).addEventListener('click', function () { contact.close(); });
    contact.addEventListener('click', function (e) { if (e.target === contact) contact.close(); });
  }
  var auto = $('[data-autofocus]');
  if (auto) auto.focus();

  /* ---------- Contagem regressiva ---------- */
  var counters = $$('[data-countdown]');
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function tick() {
    var now = Date.now();
    counters.forEach(function (el) {
      var start = Date.parse(el.getAttribute('data-countdown')), end = Date.parse(el.getAttribute('data-end'));
      var box = $('.count', el), msg = $('[data-msg]', el);
      if (now >= start) {
        if (box) box.hidden = true;
        if (msg) msg.classList.remove('sr');
        if (msg) msg.textContent = now < end ? T.started : T.ended;
        return;
      }
      var s = Math.floor((start - now) / 1000);
      if (box) {
        box.hidden = false;
        $('[data-d]', box).textContent = Math.floor(s / 86400);
        $('[data-h]', box).textContent = pad(Math.floor(s % 86400 / 3600));
        $('[data-m]', box).textContent = pad(Math.floor(s % 3600 / 60));
      }
      if (msg) {
        // Texto só para leitores de tela (os números já aparecem nas caixas)
        var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
        msg.classList.toggle('sr', !!box);
        msg.textContent = T.starts + ' ' + (d ? d + ' ' + T.days + ' ' : '') + (h ? h + ' h ' : '') + m + ' ' + T.min;
      }
    });
  }
  if (counters.length) { tick(); setInterval(tick, 20000); }

  /* ---------- Mapa imersivo ---------- */
  var leafletLoading = null;
  function loadLeaflet() {
    if (window.L) return Promise.resolve(window.L);
    if (leafletLoading) return leafletLoading;
    leafletLoading = new Promise(function (resolve, reject) {
      var css = document.createElement('link');
      css.rel = 'stylesheet'; css.href = '/vendor/leaflet/leaflet.css';
      document.head.appendChild(css);
      var s = document.createElement('script');
      s.src = '/vendor/leaflet/leaflet.js';
      s.onload = function () { resolve(window.L); };
      s.onerror = reject;
      document.head.appendChild(s);
    });
    return leafletLoading;
  }
  function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
  var mainMap = null, markers = {}, church = null;

  function initMap(box, focusId) {
    if (box._map) { if (focusId) flyTo(focusId); return Promise.resolve(box._map); }
    if (box._loading) return box._loading;
    var points = [];
    try { points = JSON.parse(($('[data-map-points]', box) || {}).textContent || '[]'); } catch (e) {}
    var btn = $('[data-map-load]', box);
    if (btn) { btn.disabled = true; btn.lastChild.textContent = T.mapLoading; }
    box._loading = loadLeaflet().then(function (L) {
      var el = document.createElement('div');
      el.className = 'sky-map map-dark';
      box.insertBefore(el, box.firstChild);
      var svg = $('svg.stars', box);
      if (svg) svg.style.display = 'none';
      var bar = $('.sky-bar', box);
      if (bar) bar.hidden = true;
      var map = L.map(el, { scrollWheelZoom: false, zoomControl: true, attributionControl: true });
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);
      var bounds = [];
      points.forEach(function (p) {
        if (p.type === 'church') {
          church = [p.lat, p.lng];
          L.marker(church, { icon: L.divIcon({ className: '', html: '<span class="cpin"><i></i><b>' + esc(p.title) + '</b></span>', iconSize: [0, 0] }), title: p.title, alt: p.title, zIndexOffset: 1000 })
            .addTo(map).bindPopup('<b>' + esc(p.title) + '</b>' + esc(p.text) + '<br><a href="' + esc(p.url) + '">' + esc(T.details) + '</a>');
        }
        bounds.push([p.lat, p.lng]);
      });
      points.forEach(function (p) {
        if (p.type !== 'group') return;
        var label = (p.text.split(' · ')[1] || p.title);
        if (church && !calm()) L.polyline([church, [p.lat, p.lng]], { color: '#FFB86B', weight: 1.4, opacity: .7, className: 'beam-line', interactive: false }).addTo(map);
        var m = L.marker([p.lat, p.lng], { icon: L.divIcon({ className: '', html: '<span class="gpin"><i></i><b>' + esc(label) + '</b></span>', iconSize: [0, 0] }), title: p.title, alt: p.title, keyboard: true })
          .addTo(map).bindPopup('<b>' + esc(p.title) + '</b><span class="pop-aud">' + esc(p.aud || '') + '</span>' + esc(p.text) +
            '<span class="pop-actions">' + (p.wa ? '<a class="pop-wa" href="' + esc(p.wa) + '" target="_blank" rel="noopener">' + esc(T.wa) + '</a>' : '') +
            '<a href="' + esc(p.url) + '">' + esc(T.details) + '</a></span>');
        m.on('click', function () { highlight(p.id); });
        markers[p.id] = m;
      });
      var zoom = parseInt(box.getAttribute('data-zoom') || '14', 10);
      if (bounds.length > 1) {
        var wide = window.innerWidth >= 1100 && box.closest('.stage');
        map.fitBounds(bounds, { paddingTopLeft: [wide ? 520 : 40, 60], paddingBottomRight: [40, 60], maxZoom: 15 });
      } else if (bounds.length === 1) map.setView(bounds[0], zoom);
      else map.setView([-27.645, -48.67], 12);
      // Rótulos dos bairros só aparecem com o mapa aproximado (evita texto sobreposto)
      var syncZoom = function () { el.classList.toggle('zoom-low', map.getZoom() < 13); };
      map.on('zoomend', syncZoom);
      syncZoom();
      box._map = map;
      mainMap = map;
      if (focusId) flyTo(focusId);
      return map;
    }).catch(function () {
      if (btn) { btn.disabled = false; btn.lastChild.textContent = T.mapFail; }
      box._loading = null;
    });
    return box._loading;
  }
  function highlight(id) {
    $$('[data-group]').forEach(function (c) { c.classList.toggle('active', c.getAttribute('data-id') === String(id)); });
    Object.keys(markers).forEach(function (k) {
      var el = markers[k].getElement();
      if (el) { var pin = el.querySelector('.gpin'); if (pin) pin.classList.toggle('on', k === String(id)); }
    });
    var card = $('[data-group][data-id="' + id + '"]');
    var panel = card && card.closest('.stage-panel');
    if (card && panel && window.innerWidth >= 1100) card.scrollIntoView({ block: 'nearest', behavior: calm() ? 'auto' : 'smooth' });
  }
  function flyTo(id) {
    var m = markers[id];
    if (!m || !mainMap) return;
    var ll = m.getLatLng();
    // Na página de grupos (tela larga) a lista cobre a esquerda: o pino vai para a parte livre do mapa
    var box = mainMap.getContainer().closest('.stage');
    if (box && window.innerWidth >= 1100) ll = mainMap.unproject(mainMap.project(ll, 15).subtract([240, 0]), 15);
    if (calm()) mainMap.setView(ll, 15); else mainMap.flyTo(ll, 15, { duration: 1.2 });
    setTimeout(function () { m.openPopup(); }, calm() ? 0 : 1250);
    highlight(id);
  }
  var boxes = $$('[data-map]');
  boxes.forEach(function (box) {
    var btn = $('[data-map-load]', box);
    if (btn) btn.addEventListener('click', function () { initMap(box); });
    if (!slowNet && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { io.disconnect(); initMap(box); } }, { rootMargin: '150px' });
      io.observe(box);
    }
  });
  document.addEventListener('click', function (e) {
    var f = e.target.closest('[data-fly]');
    if (!f || !boxes[0]) return;
    var id = f.getAttribute('data-fly');
    initMap(boxes[0], id);
    if (window.innerWidth < 1100) boxes[0].scrollIntoView({ behavior: calm() ? 'auto' : 'smooth', block: 'center' });
  });

  /* ---------- Filtros e "perto de mim" ---------- */
  var list = $('[data-group-list]');
  if (list) {
    var cards = $$('[data-group]', list), search = $('[data-group-search]'), day = '', empty = $('[data-group-empty]');
    var norm = function (t) { return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    function apply() {
      var q = norm(search ? search.value : ''), shown = 0;
      cards.forEach(function (c) {
        var ok = (!q || norm(c.getAttribute('data-search')).indexOf(q) !== -1) && (day === '' || c.getAttribute('data-weekday') === day);
        c.hidden = !ok;
        var m = markers[c.getAttribute('data-id')];
        if (m && m.getElement()) m.getElement().style.opacity = ok ? '' : '.25';
        if (ok) shown++;
      });
      if (empty) empty.hidden = shown > 0;
    }
    if (search) search.addEventListener('input', apply);
    $$('[data-day]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        day = chip.getAttribute('data-day');
        $$('[data-day]').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        apply();
      });
    });
    cards.forEach(function (c) {
      c.addEventListener('mouseenter', function () {
        var m = markers[c.getAttribute('data-id')];
        if (m && m.getElement()) { var pin = m.getElement().querySelector('.gpin'); if (pin) pin.classList.add('on'); }
      });
      c.addEventListener('mouseleave', function () {
        if (c.classList.contains('active')) return;
        var m = markers[c.getAttribute('data-id')];
        if (m && m.getElement()) { var pin = m.getElement().querySelector('.gpin'); if (pin) pin.classList.remove('on'); }
      });
    });
    var near = $('[data-near]'), nearStatus = $('[data-near-status]');
    if (near && 'geolocation' in navigator && cards.some(function (c) { return c.hasAttribute('data-lat'); })) {
      near.hidden = false;
      near.addEventListener('click', function () {
        nearStatus.hidden = false;
        nearStatus.textContent = T.locating;
        navigator.geolocation.getCurrentPosition(function (pos) {
          var la = pos.coords.latitude, lo = pos.coords.longitude, rad = Math.PI / 180;
          cards.forEach(function (c) {
            if (!c.hasAttribute('data-lat')) { c._d = 1e9; return; }
            var gl = parseFloat(c.getAttribute('data-lat')), gn = parseFloat(c.getAttribute('data-lng'));
            var a = Math.pow(Math.sin((gl - la) * rad / 2), 2) + Math.cos(la * rad) * Math.cos(gl * rad) * Math.pow(Math.sin((gn - lo) * rad / 2), 2);
            c._d = 12742 * Math.asin(Math.sqrt(a));
            var tag = $('[data-dist]', c);
            tag.hidden = false;
            tag.textContent = c._d < 1 ? Math.round(c._d * 1000) + ' m' : c._d.toFixed(1).replace('.', T.lang === 'en' ? '.' : ',') + ' km';
          });
          cards.sort(function (a, b) { return a._d - b._d; }).forEach(function (c) { list.appendChild(c); });
          nearStatus.textContent = T.sorted;
          var nearest = cards[0];
          if (boxes[0] && nearest && nearest._d < 1e9) {
            initMap(boxes[0]).then(function (map) {
              if (!map || !window.L) return;
              var Lf = window.L, me = [la, lo], to = [parseFloat(nearest.getAttribute('data-lat')), parseFloat(nearest.getAttribute('data-lng'))];
              Lf.marker(me, { icon: Lf.divIcon({ className: '', html: '<span class="mepin"></span>', iconSize: [0, 0] }), title: T.here }).addTo(map).bindPopup(T.here);
              Lf.polyline([me, to], { color: '#7fb2ff', weight: 2, className: 'near-line', interactive: false }).addTo(map);
              map.fitBounds([me, to], { padding: [80, 80], maxZoom: 15 });
              highlight(nearest.getAttribute('data-id'));
            });
          }
        }, function () { nearStatus.textContent = T.noLoc; }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 });
      });
    }
  }


  /* ---------- Guia de negócios: busca ao vivo ---------- */
  var bizForm = $('[data-biz-search]');
  if (bizForm) {
    var bizInput = $('input[name="q"]', bizForm), grid = $('[data-biz-results]'), bizStatus = $('[data-biz-status]'), tpl = $('#biz-card-tpl');
    var emptyBox = $('[data-biz-empty]'), timer = null, lastQ = bizInput.value, ctrl = null;
    // Exemplos girando no campo (só quando vazio e sem foco)
    var ex = (bizInput.getAttribute('data-examples') || '').split('|').filter(Boolean), exI = 0, basePh = bizInput.placeholder;
    if (ex.length && !calm()) setInterval(function () {
      if (document.activeElement === bizInput || bizInput.value) return;
      bizInput.placeholder = basePh + ' ' + ex[exI++ % ex.length] + '…';
    }, 2600);
    function hl(text, q) {
      var safe = esc(text);
      q.split(/\s+/).filter(function (w) { return w.length > 2; }).forEach(function (w) {
        var norm = w.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        safe = safe.replace(new RegExp('(' + norm + ')', 'gi'), '<mark>$1</mark>');
      });
      return safe;
    }
    function render(data, q) {
      grid.innerHTML = '';
      data.results.forEach(function (r) {
        var n = tpl.content.firstElementChild.cloneNode(true);
        var main = $('.biz-main', n); main.href = r.url;
        var logo = $('.biz-logo', n); logo.classList.add('cat-' + r.icon);
        if (r.logo) { logo.innerHTML = '<img src="' + esc(r.logo) + '" width="72" height="72" alt="" loading="lazy">'; }
        else { $('b', logo).textContent = (r.name || 'G').charAt(0).toUpperCase(); }
        $('.biz-cat', n).textContent = r.cat;
        $('h3', n).innerHTML = hl(r.name, q);
        $('.biz-desc', n).innerHTML = hl(r.desc, q);
        $('.biz-meta', n).textContent = r.place || '';
        var wa = $('.biz-wa', n); wa.href = r.wa;
        if (r.featured) n.classList.add('featured');
        grid.appendChild(n);
      });
      var html = '';
      if (q) html += '<p><b>' + data.total + '</b> ' + (data.total === 1 ? T.bizResult : T.bizResults) + ' “' + esc(q) + '”</p>';
      if (data.suggestion && data.suggestion !== q.toLowerCase()) html += '<p>' + esc(T.bizDidYouMean) + ' <a href="?q=' + encodeURIComponent(data.suggestion) + '"><b>' + esc(data.suggestion) + '</b></a>?</p>';
      if (!data.total) html += '<p>' + esc(T.bizNone) + '</p>';
      bizStatus.innerHTML = html;
      if (emptyBox) emptyBox.hidden = data.total > 0;
    }
    function run() {
      var q = bizInput.value.trim();
      if (q === lastQ) return;
      lastQ = q;
      if (ctrl) ctrl.abort();
      ctrl = 'AbortController' in window ? new AbortController() : null;
      var url = '/api/negocios?lang=' + bizForm.getAttribute('data-lang') + '&cat=' + encodeURIComponent(bizForm.getAttribute('data-cat') || '') + '&q=' + encodeURIComponent(q);
      fetch(url, ctrl ? { signal: ctrl.signal } : {}).then(function (r) { return r.json(); }).then(function (data) {
        render(data, q);
        var u = new URL(location.href);
        if (q) u.searchParams.set('q', q); else u.searchParams.delete('q');
        history.replaceState(null, '', u.pathname + u.search);
      }).catch(function () {});
    }
    bizInput.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(run, slowNet ? 600 : 250); });
    bizForm.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); lastQ = null; run(); });
  }

  /* ---------- Compartilhar ---------- */
  document.addEventListener('click', function (e) {
    var s = e.target.closest('[data-share]');
    if (!s) return;
    if (navigator.share) navigator.share({ title: s.getAttribute('data-title') || document.title, url: location.href }).catch(function () {});
    else if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(function () { s.lastChild.textContent = T.copied; });
  });

  /* ---------- Copiar endereço ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b || !navigator.clipboard) return;
    navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
      var old = b.innerHTML; b.textContent = T.copied; setTimeout(function () { b.innerHTML = old; }, 2000);
    }, function () {});
  });
})();
