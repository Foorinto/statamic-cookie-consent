/* ==========================================================================
   Foorintodev Cookie Consent — bandeau et chargement des services après accord
   --------------------------------------------------------------------------
   Sans dépendance ni compilation. Lit la config écrite dans la page par la balise
   {{ cookie_consent }} (#cookie-consent-config) :
     version       empreinte des services : si elle change, l'accord est redemandé
     storageKey    clé du choix dans localStorage (pas un cookie)
     lifetimeDays  durée de validité d'un choix
     categories    catégories proposées (necessary toujours acceptée)
     services      [{ id, category, loader, cookies }]
     report        adresse de signalement des noms non déclarés (null : détection coupée)
     ignore        noms à ne jamais signaler (« * » = n'importe quelle suite de caractères)
   Rien n'est chargé chez un tiers avant l'accord pour la catégorie du service.

   Détection : quelques secondes après l'affichage (et après un choix), les noms des
   cookies et des données stockées qui ne sont déclarés dans aucun service sont envoyés
   au site, sans leur contenu, sans cookie, au plus une fois par semaine pour chaque nom.

   API pour le site :
     window.CookieConsent.open()             rouvre le bandeau
     window.CookieConsent.has('analytics')   catégorie acceptée ?
     window.CookieConsent.onChange(fn)       fn({ necessary: true, analytics: …, … })
     évènement « cookie-consent:change » sur document, même détail
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-cookie-consent]');
  var configEl = document.getElementById('cookie-consent-config');
  if (!root || !configEl) return;

  var config;
  try { config = JSON.parse(configEl.textContent); } catch (e) { return; }

  var optional = config.categories.filter(function (c) { return c !== 'necessary'; });
  var prefs = root.querySelector('[data-cc-prefs]');
  var customize = root.querySelector('[data-cc-customize]');
  var switches = Array.prototype.slice.call(root.querySelectorAll('[data-cc-category]'));
  var current = null;      // choix en vigueur : { v, at, categories: { analytics: true, … } }
  var started = {};        // services déjà démarrés sur cette page
  var listeners = [];
  var opener = null;

  /* ------------------------------------------------------------ mémoire */
  function stored() {
    try { return JSON.parse(localStorage.getItem(config.storageKey)); } catch (e) { return null; }
  }

  function valid(s) {
    return !!s && s.v === config.version && s.categories && typeof s.categories === 'object'
      && Date.now() - s.at < config.lifetimeDays * 864e5;
  }

  function save(categories) {
    var s = { v: config.version, at: Date.now(), categories: categories };
    // Noms déjà signalés (détection), y compris depuis cette page avant le choix.
    var old = stored();
    var today = Math.floor(Date.now() / 864e5);
    s.r = (old && old.r) || {};
    Object.keys(sent || {}).forEach(function (key) { s.r[key] = today; });
    try { localStorage.setItem(config.storageKey, JSON.stringify(s)); } catch (e) {}
    return s;
  }

  /* ------------------------------------------------------------ noms */
  function pattern(p) {
    return new RegExp('^' + String(p).split('*').map(function (part) {
      return part.replace(/[.+?^${}()|[\]\\]/g, '\\$&');
    }).join('.*') + '$');
  }

  function matches(name, patterns) {
    for (var i = 0; i < patterns.length; i++) {
      var p = patterns[i];
      if (p === name || (p.indexOf('*') !== -1 && pattern(p).test(name))) return true;
    }
    return false;
  }

  // Une longue suite hexadécimale avec au moins un chiffre (souvent un identifiant propre
  // au visiteur) devient « * » avant l'envoi. Même règle côté serveur.
  function normalize(name) {
    return String(name).trim().replace(/[0-9a-fA-F]{12,}/g, function (m) { return /\d/.test(m) ? '*' : m; });
  }

  // Noms présents dans ce navigateur : [{ name, storage: cookie | local | session }].
  function present() {
    var out = [];
    document.cookie.split(';').forEach(function (part) {
      var name = part.split('=')[0].trim();
      if (name) out.push({ name: name, storage: 'cookie' });
    });
    ['local', 'session'].forEach(function (type) {
      try {
        var store = window[type + 'Storage'];
        for (var i = 0; i < store.length; i++) out.push({ name: store.key(i), storage: type });
      } catch (e) {}
    });
    return out;
  }

  function granted(category) {
    return category === 'necessary' || !!(current && current.categories[category]);
  }

  function snapshot() {
    var out = { necessary: true };
    optional.forEach(function (c) { out[c] = granted(c); });
    return out;
  }

  /* ------------------------------------------------------------ services */
  function addScript(src) {
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
  }

  // Code d'un service personnalisé : les <script> doivent être recréés pour s'exécuter.
  function runScript(old, parent, before) {
    var s = document.createElement('script');
    Array.prototype.forEach.call(old.attributes, function (a) { s.setAttribute(a.name, a.value); });
    s.async = false; // garde l'ordre des scripts externes
    s.text = old.text;
    parent.insertBefore(s, before || null);
    if (old.parentNode) old.parentNode.removeChild(old);
  }

  function injectHtml(html) {
    var tpl = document.createElement('template');
    tpl.innerHTML = html;
    Array.prototype.slice.call(tpl.content.childNodes).forEach(function (node) {
      if (node.nodeName === 'SCRIPT') { runScript(node, document.body); return; }
      document.body.appendChild(node);
      if (node.querySelectorAll) {
        Array.prototype.slice.call(node.querySelectorAll('script')).forEach(function (old) {
          runScript(old, old.parentNode, old);
        });
      }
    });
  }

  var loaders = {
    meta_pixel: {
      start: function (l) {
        if (!window.fbq) {
          var n = window.fbq = function () {
            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
          };
          if (!window._fbq) window._fbq = n;
          n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
          addScript('https://connect.facebook.net/en_US/fbevents.js');
        }
        window.fbq('init', l.pixelId);
        window.fbq('track', 'PageView');
      },
      resume: function () { if (window.fbq) window.fbq('consent', 'grant'); },
      stop: function () { if (window.fbq) window.fbq('consent', 'revoke'); }
    },
    google_analytics: {
      start: function (l) {
        window['ga-disable-' + l.measurementId] = false;
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', l.measurementId);
        addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(l.measurementId));
      },
      resume: function (l) { window['ga-disable-' + l.measurementId] = false; },
      stop: function (l) { window['ga-disable-' + l.measurementId] = true; }
    },
    html: {
      start: function (l) { injectHtml(l.html); }
      // Code libre : impossible de l'arrêter proprement, ses cookies sont effacés
      // et il ne se recharge plus aux pages suivantes.
    }
  };

  function deleteCookie(name) {
    var parts = location.hostname.split('.');
    var domains = [''];
    for (var i = 0; i < parts.length - 1; i++) {
      var d = parts.slice(i).join('.');
      domains.push('; domain=' + d, '; domain=.' + d);
    }
    domains.forEach(function (d) {
      document.cookie = name + '=; Max-Age=0; path=/' + d;
    });
  }

  function erase(service) {
    var here = present();
    (service.cookies || []).forEach(function (c) {
      // Un nom avec « * » efface tout ce qui y correspond.
      var names = c.name.indexOf('*') === -1 ? [c.name] : here
        .filter(function (item) { return item.storage === (c.storage || 'cookie') && matches(item.name, [c.name]); })
        .map(function (item) { return item.name; });
      names.forEach(function (name) {
        try {
          if (c.storage === 'local') localStorage.removeItem(name);
          else if (c.storage === 'session') sessionStorage.removeItem(name);
          else deleteCookie(name);
        } catch (e) {}
      });
    });
  }

  /* ------------------------------------------------------------ détection */
  var known = [config.storageKey].concat(config.ignore || []);
  config.services.forEach(function (service) {
    (service.cookies || []).forEach(function (c) { known.push(c.name); });
  });
  var sent = {};      // déjà envoyés depuis cette page
  var detectTimer = null;

  // Garde la date d'envoi de chaque nom dans le choix enregistré (s'il existe), pour ne
  // pas le renvoyer avant une semaine. Sans choix enregistré : seulement pour cette page.
  function remember(items, today) {
    var s = stored();
    if (!s) return;
    var r = s.r || {};
    Object.keys(r).forEach(function (key) { if (today - r[key] > 30) delete r[key]; });
    items.forEach(function (item) { r[item.storage + ':' + item.name] = today; });
    s.r = r;
    try { localStorage.setItem(config.storageKey, JSON.stringify(s)); } catch (e) {}
  }

  function detect() {
    if (!config.report || !window.fetch) return;
    var s = stored();
    var memo = (s && s.r) || {};
    var today = Math.floor(Date.now() / 864e5);
    var items = [];

    present().forEach(function (item) {
      var name = normalize(item.name);
      var key = item.storage + ':' + name;
      if (matches(item.name, known) || matches(name, known)) return;
      if (sent[key] || (memo[key] && today - memo[key] < 7)) return;
      sent[key] = true;
      items.push({ name: name, storage: item.storage });
    });

    if (!items.length) return;
    items = items.slice(0, 20);
    remember(items, today);

    fetch(config.report, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: items, page: location.pathname }),
      credentials: 'omit',
      keepalive: true
    }).catch(function () {});
  }

  // Les outils déposent souvent leurs cookies un peu après le chargement.
  function scheduleDetect() {
    clearTimeout(detectTimer);
    detectTimer = setTimeout(detect, 5000);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') detect();
  });

  function apply() {
    config.services.forEach(function (service) {
      var loader = service.loader && loaders[service.loader.type];
      if (granted(service.category)) {
        if (!loader) return;
        if (started[service.id] === undefined) { started[service.id] = true; loader.start(service.loader); }
        else if (started[service.id] === false) { started[service.id] = true; if (loader.resume) loader.resume(service.loader); }
      } else if (service.category !== 'necessary') {
        if (started[service.id] && loader && loader.stop) loader.stop(service.loader);
        if (started[service.id]) started[service.id] = false;
        erase(service);
      }
    });

    var detail = snapshot();
    listeners.forEach(function (fn) { try { fn(detail); } catch (e) {} });
    document.dispatchEvent(new CustomEvent('cookie-consent:change', { detail: detail }));
  }

  /* ------------------------------------------------------------ bandeau */
  function openPrefs(open) {
    prefs.hidden = !open;
    customize.setAttribute('aria-expanded', String(open));
  }

  function syncSwitches() {
    var s = current || stored();
    var chosen = (s && s.categories) || {};
    switches.forEach(function (box) { box.checked = !!chosen[box.getAttribute('data-cc-category')]; });
  }

  function show(withPrefs, focus) {
    syncSwitches();
    openPrefs(!!withPrefs);
    root.hidden = false;
    if (focus) root.focus();
  }

  function hide() {
    var hadFocus = root.contains(document.activeElement);
    root.hidden = true;
    openPrefs(false);
    // Rouvert depuis le pied de page : le focus y retourne. Sinon, le bandeau étant placé
    // en haut de la page, le focus repart naturellement du début.
    if (hadFocus && opener) opener.focus();
    opener = null;
  }

  function choose(categories) {
    current = save(categories);
    hide();
    apply();
    scheduleDetect();
  }

  function all(value) {
    var out = {};
    optional.forEach(function (c) { out[c] = value; });
    return out;
  }

  root.querySelector('[data-cc-accept]').addEventListener('click', function () { choose(all(true)); });
  root.querySelector('[data-cc-decline]').addEventListener('click', function () { choose(all(false)); });
  root.querySelector('[data-cc-save]').addEventListener('click', function () {
    var out = all(false);
    switches.forEach(function (box) { out[box.getAttribute('data-cc-category')] = box.checked; });
    choose(out);
  });
  customize.addEventListener('click', function () {
    openPrefs(prefs.hidden);
    if (!prefs.hidden) {
      var first = prefs.querySelector('[data-cc-category]') || prefs.querySelector('summary');
      if (first) first.focus();
    }
  });

  // Échap referme le bandeau quand un choix existe déjà (le choix ne change pas).
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !root.hidden && current) hide();
  });

  // Boutons « Gérer les cookies » : cachés tant que ce script n'a pas démarré.
  Array.prototype.forEach.call(document.querySelectorAll('[data-cookie-consent-open]'), function (b) {
    b.hidden = false;
  });
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-cookie-consent-open]');
    if (!b) return;
    e.preventDefault();
    opener = b;
    show(true, true);
  });

  window.CookieConsent = {
    open: function () { show(true, true); },
    has: granted,
    onChange: function (fn) { listeners.push(fn); if (current) fn(snapshot()); }
  };

  /* ------------------------------------------------------------ démarrage */
  var s = stored();
  if (valid(s)) {
    current = s;
    apply();
  } else {
    // Pas de choix valable (premier passage, choix expiré ou services modifiés) :
    // rien n'est chargé, les traces d'un ancien accord sont effacées, le bandeau s'affiche.
    apply();
    show(false, false);
  }
  scheduleDetect();
})();
