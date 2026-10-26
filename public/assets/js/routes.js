/* DSK - cálculo da distância (OpenStreetMap) e do valor da rota
 * - Nominatim (OSM): converte o endereço em coordenadas
 * - OSRM (dados OSM): calcula a distância real por ruas
 * Serviços gratuitos, sem chave de API.
 */
(function () {
  var form = document.getElementById('formRota');
  if (!form) return;

  var origemInput = document.getElementById('origem');
  var destinoInput = document.getElementById('destino');
  var kmInput = document.getElementById('km');
  var valorKmInput = document.getElementById('valor_km');
  var idaVoltaInput = document.getElementById('ida_volta');
  var kmTotal = document.getElementById('kmTotal');
  var valorReceber = document.getElementById('valorReceber');
  var btnCalc = document.getElementById('btnCalcKm');
  var status = document.getElementById('kmStatus');

  /* Caixa de busca em torno de Joinville/SC (esq, topo, dir, base) */
  var VIEWBOX = '-49.20,-26.12,-48.60,-26.45';
  var geoCache = {};
  var runId = 0;
  var debounce = null;

  function num(value) {
    value = String(value || '').trim();
    if (value.indexOf(',') > -1) value = value.replace(/\./g, '');
    value = value.replace(',', '.');
    var parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
  }

  function brl(value) {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function recalculate() {
    var multiplier = idaVoltaInput.checked ? 2 : 1;
    var km = num(kmInput.value);
    kmTotal.textContent = (km * multiplier).toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' km';
    valorReceber.textContent = brl(km * multiplier * num(valorKmInput.value));
  }

  function setStatus(type, html) {
    if (!status) return;
    var colors = { info: 'var(--text-dim)', ok: 'var(--success)', error: 'var(--danger)' };
    status.style.color = colors[type] || colors.info;
    status.innerHTML = html;
  }

  function esc(text) {
    var d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
  }

  function sleep(ms) {
    return new Promise(function (resolve) { setTimeout(resolve, ms); });
  }

  function fetchJson(url) {
    return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    });
  }

  /* Endereço -> {lat, lon, nome}. Tenta primeiro dentro de Joinville. */
  function geocode(address) {
    var key = address.toLowerCase();
    if (geoCache[key]) return Promise.resolve({ data: geoCache[key], fromCache: true });

    var attempts = [];
    if (!/joinville/i.test(address)) attempts.push(address + ', Joinville, SC');
    attempts.push(address);

    var chain = Promise.resolve(null);
    attempts.forEach(function (q, i) {
      chain = chain.then(function (found) {
        if (found) return found;
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=br' +
          '&accept-language=pt-BR&viewbox=' + VIEWBOX + '&q=' + encodeURIComponent(q);
        return (i > 0 ? sleep(1100) : Promise.resolve()).then(function () {
          return fetchJson(url);
        }).then(function (list) {
          if (!list || !list.length) return null;
          return { lat: parseFloat(list[0].lat), lon: parseFloat(list[0].lon), nome: list[0].display_name };
        });
      });
    });

    return chain.then(function (found) {
      if (found) geoCache[key] = found;
      return { data: found, fromCache: false };
    });
  }

  /* Distância por ruas (km) + traçado da rota entre dois pontos */
  function drivingKm(a, b) {
    var url = 'https://router.project-osrm.org/route/v1/driving/' +
      a.lon + ',' + a.lat + ';' + b.lon + ',' + b.lat + '?overview=full&geometries=geojson';
    return fetchJson(url).then(function (json) {
      if (!json.routes || !json.routes.length) throw new Error('sem rota');
      var route = json.routes[0];
      return { km: route.distance / 1000, coords: route.geometry.coordinates };
    });
  }

  /* Mapa (Leaflet + tiles do OpenStreetMap) */
  var mapEl = document.getElementById('mapaRota');
  var map = null, routeLayer = null;

  function showMap(pOrigem, pDestino, coords) {
    if (!mapEl || typeof L === 'undefined') return;
    mapEl.style.display = 'block';
    if (!map) {
      map = L.map(mapEl, { scrollWheelZoom: false });
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      }).addTo(map);
    }
    if (routeLayer) map.removeLayer(routeLayer);
    var latlngs = coords.map(function (c) { return [c[1], c[0]]; });
    var line = L.polyline(latlngs, { color: '#d4af6a', weight: 5, opacity: 0.9 });
    var start = L.circleMarker([pOrigem.lat, pOrigem.lon], { radius: 9, color: '#fff', weight: 2, fillColor: '#3f9b6e', fillOpacity: 1 })
      .bindTooltip('Origem');
    var end = L.circleMarker([pDestino.lat, pDestino.lon], { radius: 9, color: '#fff', weight: 2, fillColor: '#d9534f', fillOpacity: 1 })
      .bindTooltip('Destino');
    routeLayer = L.layerGroup([line, start, end]).addTo(map);
    map.invalidateSize();
    map.fitBounds(line.getBounds(), { padding: [30, 30] });
  }

  function calculateDistance() {
    var origem = origemInput.value.trim();
    var destino = destinoInput.value.trim();
    if (origem.length < 3 || destino.length < 3) {
      setStatus('info', 'Preencha origem e destino para calcular a distância automaticamente.');
      return;
    }

    var myRun = ++runId;
    if (btnCalc) btnCalc.disabled = true;
    setStatus('info', '<i class="bi bi-hourglass-split"></i> Calculando distância no OpenStreetMap...');

    var pOrigem, pDestino;
    geocode(origem).then(function (r1) {
      if (myRun !== runId) throw 'cancelado';
      if (!r1.data) throw { msg: 'Não encontrei a origem no mapa. Tente incluir rua, número e bairro.' };
      pOrigem = r1.data;
      return (r1.fromCache ? Promise.resolve() : sleep(1100)).then(function () { return geocode(destino); });
    }).then(function (r2) {
      if (myRun !== runId) throw 'cancelado';
      if (!r2.data) throw { msg: 'Não encontrei o destino no mapa. Tente incluir rua, número e bairro.' };
      pDestino = r2.data;
      return drivingKm(pOrigem, pDestino);
    }).then(function (result) {
      if (myRun !== runId) return;
      var km = result.km;
      showMap(pOrigem, pDestino, result.coords);
      kmInput.value = km.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      recalculate();
      setStatus('ok', '<i class="bi bi-check-circle"></i> Distância de ida calculada: <b>' +
        kmInput.value + ' km</b>. Você ainda pode ajustar o valor manualmente.' +
        '<br><small class="text-faint">De: ' + esc(pOrigem.nome) + '<br>Para: ' + esc(pDestino.nome) + '</small>');
    }).catch(function (err) {
      if (err === 'cancelado') return;
      var msg = (err && err.msg) ? err.msg :
        'Não foi possível calcular agora. Verifique a conexão ou digite os km manualmente.';
      setStatus('error', '<i class="bi bi-exclamation-triangle"></i> ' + esc(msg));
    }).then(function () {
      if (myRun === runId && btnCalc) btnCalc.disabled = false;
    });
  }

  function scheduleCalculation() {
    clearTimeout(debounce);
    debounce = setTimeout(calculateDistance, 600);
  }

  /* Calcula ao sair do campo (não a cada tecla: respeita o limite do Nominatim) */
  origemInput.addEventListener('change', scheduleCalculation);
  destinoInput.addEventListener('change', scheduleCalculation);
  if (btnCalc) btnCalc.addEventListener('click', calculateDistance);

  kmInput.addEventListener('input', recalculate);
  valorKmInput.addEventListener('input', recalculate);
  idaVoltaInput.addEventListener('change', recalculate);
  recalculate();
})();
