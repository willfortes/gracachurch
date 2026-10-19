/* Painel Graça Church */
(function () {
  'use strict';
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };

  // Menu no celular
  var menuBtn = $('[data-menu]'), side = $('#menu');
  if (menuBtn && side) {
    menuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = side.classList.toggle('open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) { if (!side.contains(e.target)) side.classList.remove('open'); });
  }

  // Confirmação antes de excluir
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (e.submitter && e.submitter.hasAttribute('data-confirm-btn')) msg = 'Excluir esta pergunta? Isso não pode ser desfeito.';
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // Mostra só os campos que valem para o tipo de recorrência
  var rec = $('[name="recurrence"]');
  function syncRecurrence() {
    if (!rec) return;
    var v = rec.value;
    $$('[data-show]').forEach(function (el) {
      el.hidden = el.getAttribute('data-show').split(' ').indexOf(v) === -1;
    });
  }
  if (rec) { rec.addEventListener('change', syncRecurrence); syncRecurrence(); }

  // Contadores de caracteres
  $$('[data-count]').forEach(function (input) {
    var out = $('#' + input.getAttribute('data-count'));
    var max = parseInt(input.getAttribute('maxlength'), 10);
    var upd = function () { out.textContent = input.value.length + ' / ' + max; };
    input.addEventListener('input', upd); upd();
  });

  // Prévia do aviso push
  var pt = $('[name="title"][data-preview]'), pb = $('[name="body"][data-preview]');
  if (pt && pb) {
    var upd = function () { $('#pv-title').textContent = pt.value || 'Título do aviso'; $('#pv-body').textContent = pb.value || 'Mensagem do aviso'; };
    pt.addEventListener('input', upd); pb.addEventListener('input', upd); upd();
    var aud = $('[name="audience"]'), info = $('#aud-count');
    if (aud && info) {
      var counts = JSON.parse(aud.getAttribute('data-counts') || '{}');
      var u = function () { var n = counts[aud.value] || 0; info.textContent = n + ' aparelho' + (n === 1 ? '' : 's') + ' vão receber.'; };
      aud.addEventListener('change', u); u();
    }
  }

  // Alertas de novos pedidos de ajuda neste aparelho (equipe)
  var teamBtn = $('[data-team-push]');
  if (teamBtn) teamBtn.addEventListener('click', function () {
    var st = $('[data-team-status]');
    var key = ($('meta[name="vapid-key"]') || {}).content || '';
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !key) { st.textContent = 'Este navegador não aceita avisos, ou as chaves VAPID não foram configuradas.'; return; }
    teamBtn.disabled = true;
    var raw = atob((key + '='.repeat((4 - key.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
    var appKey = Uint8Array.from(raw, function (c) { return c.charCodeAt(0); });
    Notification.requestPermission().then(function (p) {
      if (p !== 'granted') throw new Error('Permissão negada. Libere as notificações nas configurações do navegador.');
      return navigator.serviceWorker.register('/sw.js').then(function () { return navigator.serviceWorker.ready; });
    }).then(function (reg) {
      return reg.pushManager.getSubscription().then(function (s) { return s || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: appKey }); });
    }).then(function (sub) {
      return fetch('/admin/alertas-equipe', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': ($('meta[name="csrf"]') || {}).content || '' }, body: JSON.stringify({ subscription: sub.toJSON() }) });
    }).then(function (r) {
      if (!r.ok) throw new Error('Não deu certo. Recarregue a página e tente de novo.');
      st.textContent = 'Pronto! Este aparelho vai receber um alerta a cada pedido novo.';
    }).catch(function (e) { st.textContent = e.message; }).finally(function () { teamBtn.disabled = false; });
  });

  // Mapa para marcar o local (Leaflet carregado sob demanda)
  var box = $('[data-pick-map]');
  if (!box) return;
  var latIn = $('[name="lat"]'), lngIn = $('[name="lng"]');
  function loadLeaflet(cb) {
    if (window.L) return cb(window.L);
    var css = document.createElement('link'); css.rel = 'stylesheet'; css.href = '/vendor/leaflet/leaflet.css'; document.head.appendChild(css);
    var s = document.createElement('script'); s.src = '/vendor/leaflet/leaflet.js'; s.onload = function () { cb(window.L); }; document.head.appendChild(s);
  }
  loadLeaflet(function (L) {
    var start = [parseFloat(latIn.value) || parseFloat(box.getAttribute('data-lat')) || -27.645, parseFloat(lngIn.value) || parseFloat(box.getAttribute('data-lng')) || -48.67];
    var map = L.map(box).setView(start, latIn.value ? 16 : 12);
    var det = box.closest('details');
    if (det) det.addEventListener('toggle', function () { if (det.open) map.invalidateSize(); });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    var icon = L.divIcon({ className: '', html: '<div class="pin"></div>', iconSize: [30, 30], iconAnchor: [15, 30] });
    var marker = null;
    function place(lat, lng, zoom) {
      latIn.value = lat.toFixed(6); lngIn.value = lng.toFixed(6);
      if (!marker) {
        marker = L.marker([lat, lng], { icon: icon, draggable: true }).addTo(map);
        marker.on('dragend', function () { var p = marker.getLatLng(); place(p.lat, p.lng); });
      } else marker.setLatLng([lat, lng]);
      if (zoom) map.setView([lat, lng], zoom);
    }
    if (latIn.value && lngIn.value) place(parseFloat(latIn.value), parseFloat(lngIn.value));
    map.on('click', function (e) { place(e.latlng.lat, e.latlng.lng); });
    [latIn, lngIn].forEach(function (i) {
      i.addEventListener('change', function () {
        var a = parseFloat(latIn.value), b = parseFloat(lngIn.value);
        if (!isNaN(a) && !isNaN(b)) place(a, b, 16);
      });
    });

    // Busca de endereço (OpenStreetMap / Nominatim)
    var q = $('[data-geo-q]'), btn = $('[data-geo-btn]'), results = $('[data-geo-results]');
    if (!q || !btn) return;
    function search() {
      var text = q.value.trim();
      if (!text) return;
      results.innerHTML = '<p class="hint">Buscando…</p>';
      fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&countrycodes=br&accept-language=pt-BR&q=' + encodeURIComponent(text))
        .then(function (r) { return r.json(); })
        .then(function (list) {
          results.innerHTML = '';
          if (!list.length) { results.innerHTML = '<p class="hint">Nada encontrado. Tente com rua, bairro e cidade, ou clique no mapa.</p>'; return; }
          list.forEach(function (it) {
            var b = document.createElement('button');
            b.type = 'button'; b.textContent = it.display_name;
            b.addEventListener('click', function () { place(parseFloat(it.lat), parseFloat(it.lon), 17); results.innerHTML = '<p class="hint">Local marcado. Arraste o alfinete para ajustar.</p>'; });
            results.appendChild(b);
          });
        })
        .catch(function () { results.innerHTML = '<p class="hint">A busca falhou. Clique direto no mapa para marcar.</p>'; });
    }
    btn.addEventListener('click', search);
    q.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); search(); } });
  });
})();
