(function () {
  'use strict';

  // 1. Initialisation et détection du script courant
  var currentScript = document.currentScript || document.querySelector('script[data-website-id]');
  if (!currentScript) return;

  var websiteId = currentScript.getAttribute('data-website-id') || currentScript.getAttribute('data-sdk-key');
  if (!websiteId) {
    console.warn('[MetricsDash] Aucun websiteId (data-website-id="pk_live_...") trouvé dans la balise script.');
    return;
  }

  function sanitizeHostUrl(rawHost) {
    if (!rawHost) return '';
    var host = rawHost.trim().replace(/\/$/, '');
    // Correction des schémas malformés (ex: "http:localhost:8000", "http:/localhost:8000")
    host = host.replace(/^https?:?\/*(?!\/)/i, function (match) {
      return match.toLowerCase().indexOf('https') === 0 ? 'https://' : 'http://';
    });
    // Si aucun schéma n'est présent (ex: "localhost:8000" ou "api-dev.creerailleurs.com")
    if (!/^https?:\/\//i.test(host)) {
      if (host.indexOf('localhost') === 0 || host.indexOf('127.0.0.1') === 0) {
        host = 'http://' + host;
      } else {
        host = 'https://' + host;
      }
    }
    return host;
  }

  var scriptSrc = currentScript.src || '';
  var scriptOrigin = '';

  // Détection d'URL hôte imbriquée si le script est inclus sans schéma (ex: http://localhost:3001/fr/localhost:8000/script.js)
  if (scriptSrc) {
    var matchEmbedded = scriptSrc.match(/\/(localhost:\d+|127\.0\.0\.1:\d+|[a-z0-9.-]+\.[a-z]{2,}:\d+)/i);
    if (matchEmbedded && matchEmbedded[1]) {
      scriptOrigin = sanitizeHostUrl(matchEmbedded[1]);
    } else {
      try {
        if (/^https?:\/\//i.test(scriptSrc)) {
          scriptOrigin = new URL(scriptSrc).origin;
        }
      } catch (e) {}
    }
  }

  if (!scriptOrigin) {
    scriptOrigin = window.location.origin;
  }

  var customHost = currentScript.getAttribute('data-host-url');
  var hostUrl = customHost ? sanitizeHostUrl(customHost) : scriptOrigin;

  // Auto-upgrade HTTP vers HTTPS si la page hôte est en HTTPS pour éviter les redirections 301/308 qui provoquent NS_BINDING_ABORTED
  if (window.location.protocol === 'https:' && hostUrl.indexOf('http://') === 0 && hostUrl.indexOf('http://localhost') !== 0 && hostUrl.indexOf('http://127.0.0.1') !== 0) {
    hostUrl = hostUrl.replace('http://', 'https://');
  }

  var ingestUrl = hostUrl + '/api/v1/telemetry/sdk';

  var autoTrack = currentScript.getAttribute('data-auto-track') !== 'false';
  var autoForm = currentScript.getAttribute('data-auto-form') !== 'false';
  var excludeFormsAttr = currentScript.getAttribute('data-exclude-forms') || '';
  var excludedFormList = excludeFormsAttr ? excludeFormsAttr.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean) : [];
  var doNotTrack = currentScript.getAttribute('data-do-not-track') === 'true';

  if (doNotTrack && (navigator.doNotTrack === '1' || window.doNotTrack === '1')) {
    return;
  }

  // 2. Gestion de l'identité anonyme Visiteur & Session
  function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0,
        v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  function getSessionId() {
    try {
      var sid = sessionStorage.getItem('md_sid');
      if (!sid) {
        sid = 'sess_' + generateUUID();
        sessionStorage.setItem('md_sid', sid);
      }
      return sid;
    } catch (e) {
      return 'sess_' + generateUUID();
    }
  }

  function getVisitorId() {
    try {
      var vid = localStorage.getItem('md_vid');
      if (!vid) {
        vid = 'vis_' + generateUUID();
        localStorage.setItem('md_vid', vid);
      }
      return vid;
    } catch (e) {
      return 'vis_' + generateUUID();
    }
  }

  var sessionId = getSessionId();
  var visitorId = getVisitorId();

  // 3. Fonction d'envoi d'événement vers le backend MetricsDash
  function sendEvent(eventName, payload, contextData) {
    payload = payload || {};
    contextData = contextData || {};

    var body = {
      publicSdkKey: websiteId,
      eventName: eventName,
      visitorId: visitorId,
      sessionId: sessionId,
      payload: payload,
      context: Object.assign({
        screen: window.screen ? window.screen.width + 'x' + window.screen.height : '',
        language: navigator.language || '',
        title: document.title || '',
        url: window.location.href,
        path: window.location.pathname
      }, contextData)
    };

    var payloadStr = JSON.stringify(body);

    if (navigator.sendBeacon) {
      try {
        var blob = new Blob([payloadStr], { type: 'application/json' });
        if (navigator.sendBeacon(ingestUrl, blob)) return;
      } catch (e) {
        // Fallback fetch en cas d'échec Beacon
      }
    }

    try {
      fetch(ingestUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-SDK-Key': websiteId
        },
        body: payloadStr,
        keepalive: true
      }).catch(function () {});
    } catch (e) {}
  }

  // 4. Nettoyage d'URL & Sécurité des paramètres sensibles
  function sanitizeUrl(rawUrl) {
    try {
      var urlObj = new URL(rawUrl);
      var sensitiveKeys = ['token', 'password', 'secret', 'key', 'auth', 'access_token', 'cred', 'api_key'];
      var searchParams = urlObj.searchParams;

      sensitiveKeys.forEach(function (key) {
        if (searchParams.has(key)) {
          searchParams.set(key, '[REDACTED]');
        }
      });
      return urlObj.toString();
    } catch (e) {
      return rawUrl;
    }
  }

  function extractUtmParams(rawUrl) {
    var params = {};
    try {
      var urlObj = new URL(rawUrl);
      ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function (key) {
        if (urlObj.searchParams.has(key)) {
          params[key] = urlObj.searchParams.get(key);
        }
      });
    } catch (e) {}
    return params;
  }

  // 5. Suivi du Temps d'Engagement Actif & Rebond (Ticket 1)
  var pageStartTime = Date.now();
  var activeSeconds = 0;
  var lastActiveTime = Date.now();
  var isActive = true;
  var hasInteraction = false;
  var lastTrackedUrl = window.location.href;

  function markUserActivity() {
    lastActiveTime = Date.now();
    if (!isActive && !document.hidden) {
      isActive = true;
    }
  }

  ['mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
    window.addEventListener(evt, markUserActivity, { passive: true });
  });

  // Verification de l'inactivité (seuil de 30s) et des onglets masqués
  setInterval(function () {
    if (document.hidden) {
      isActive = false;
      return;
    }
    if (Date.now() - lastActiveTime > 30000) {
      isActive = false;
    } else {
      isActive = true;
      activeSeconds++;
    }
  }, 1000);

  function flushPageDuration() {
    var totalPageDuration = Math.max(1, Math.round((Date.now() - pageStartTime) / 1000));
    var finalActiveDuration = Math.min(activeSeconds, totalPageDuration);
    var isBounce = !hasInteraction;

    sendEvent('page_duration', {
      url: sanitizeUrl(lastTrackedUrl),
      path: window.location.pathname,
      page_duration: totalPageDuration,
      active_duration: finalActiveDuration,
      is_bounce: isBounce
    });

    // Reset pour la prochaine page
    pageStartTime = Date.now();
    activeSeconds = 0;
    lastActiveTime = Date.now();
    isActive = true;
    hasInteraction = false;
    lastTrackedUrl = window.location.href;
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      isActive = false;
      flushPageDuration();
    } else {
      pageStartTime = Date.now();
      lastActiveTime = Date.now();
      isActive = true;
    }
  });

  window.addEventListener('pagehide', flushPageDuration);

  // 6. Navigation SPAs & Vues de Page (Ticket 3)
  function trackPageView(customUrl) {
    var currentUrl = sanitizeUrl(customUrl || window.location.href);
    var utmParams = extractUtmParams(currentUrl);

    sendEvent('page_view', Object.assign({
      url: currentUrl,
      path: window.location.pathname,
      title: document.title || '',
      referrer: document.referrer || ''
    }, utmParams));
  }

  // Support SPAs (pushState / replaceState / popstate)
  var lastPathname = window.location.pathname;

  function handleRouteChange() {
    if (window.location.pathname !== lastPathname) {
      flushPageDuration();
      lastPathname = window.location.pathname;
      trackPageView();
    }
  }

  ['pushState', 'replaceState'].forEach(function (fnName) {
    var originalFn = history[fnName];
    if (originalFn) {
      history[fnName] = function () {
        var res = originalFn.apply(this, arguments);
        setTimeout(handleRouteChange, 50);
        return res;
      };
    }
  });

  window.addEventListener('popstate', function () {
    setTimeout(handleRouteChange, 50);
  });

  // 7. Capture Automatique des Clics par Délégation (Ticket 2)
  var lastClickTime = 0;
  var lastClickTarget = null;

  document.addEventListener('click', function (event) {
    if (!event.isTrusted) return; // Ignorer les clics synthétiques

    hasInteraction = true;
    markUserActivity();

    var targetEl = event.target.closest('button, a, [role="button"], input[type="submit"], input[type="button"], [data-track-click]');
    if (!targetEl) return;

    if (targetEl.hasAttribute('data-no-track')) return;

    // Déduplication clics frénétiques (< 300ms sur le même élément)
    var now = Date.now();
    if (lastClickTarget === targetEl && now - lastClickTime < 300) {
      return;
    }
    lastClickTime = now;
    lastClickTarget = targetEl;

    // Détermination du type d'élément
    var tagName = targetEl.tagName.toLowerCase();
    var elementType = 'button';
    if (tagName === 'a') elementType = 'link';
    else if (targetEl.getAttribute('role') === 'button') elementType = 'cta';
    else if (tagName === 'input') elementType = 'input';

    // Génération du sélecteur CSS
    var idPart = targetEl.id ? '#' + targetEl.id : '';
    var classPart = targetEl.className && typeof targetEl.className === 'string'
      ? '.' + targetEl.className.trim().split(/\s+/).slice(0, 2).join('.')
      : '';
    var selector = tagName + idPart + classPart;

    // Extraction et nettoyage du libellé texte
    var rawText = targetEl.innerText || targetEl.value || targetEl.getAttribute('aria-label') || targetEl.title || '';
    var cleanLabel = rawText.replace(/\s+/g, ' ').trim().slice(0, 50);

    sendEvent('click_interaction', {
      selector: selector,
      element_type: elementType,
      label: cleanLabel,
      url: sanitizeUrl(window.location.href),
      path: window.location.pathname
    });
  }, { capture: true, passive: true });

  // 8. Capture Automatique des Soumissions de Formulaires Sans PII (Support HTML Natif & JS)
  var lastSubmittedForm = null;
  var lastSubmitTime = 0;

  function isFormExcluded(form) {
    if (!form || form.tagName !== 'FORM') return true;

    // 1. Attributs d'exclusion directe
    if (form.hasAttribute('data-no-track') || form.getAttribute('data-metrics-ignore') === 'true' || form.getAttribute('data-sensitive') === 'true') {
      return true;
    }

    // 2. Vérification des ancêtres
    var parent = form.parentElement;
    while (parent && parent !== document.body) {
      if (parent.hasAttribute('data-no-track') || parent.getAttribute('data-metrics-ignore') === 'true') {
        return true;
      }
      parent = parent.parentElement;
    }

    // 3. Liste d'exclusion configurable (data-exclude-forms="id1,name2")
    var formId = (form.id || '').toLowerCase();
    var formName = (form.getAttribute('name') || '').toLowerCase();
    var formAction = (form.getAttribute('action') || '').toLowerCase();

    for (var i = 0; i < excludedFormList.length; i++) {
      var excluded = excludedFormList[i];
      if (excluded && (formId === excluded || formName === excluded || formAction.indexOf(excluded) !== -1)) {
        return true;
      }
    }

    // 4. Détection automatique des formulaires hautement sensibles (Paiement bancaire, Données de Santé)
    var hasCreditCard = !!form.querySelector('input[type="credit-card"], input[autocomplete*="cc-"], input[autocomplete*="card"], input[name*="card"], input[name*="cvv"], input[name*="iban"]');
    var hasHealthData = !!form.querySelector('input[name*="health"], input[name*="medical"], [data-category="health"], [data-category="medical"]');

    if (hasCreditCard || hasHealthData) {
      return true;
    }

    var formClasses = (form.className && typeof form.className === 'string') ? form.className.toLowerCase() : '';
    if (formClasses.indexOf('no-track') !== -1 || formClasses.indexOf('metrics-ignore') !== -1) {
      return true;
    }

    return false;
  }

  function trackFormSubmit(form, triggerStatus, isManual) {
    if (!autoForm && !isManual) return;
    if (!isManual && isFormExcluded(form)) return;

    // Déduplication (éviter double envoi si submit natif + hook JS sous 500ms)
    var now = Date.now();
    if (lastSubmittedForm === form && now - lastSubmitTime < 500) {
      return;
    }
    lastSubmittedForm = form;
    lastSubmitTime = now;

    hasInteraction = true;
    markUserActivity();

    var formId = form.id || form.getAttribute('name') || (form.getAttribute('action') ? form.getAttribute('action').split('?')[0] : '') || 'unnamed_form';
    var formName = form.getAttribute('name') || '';
    var formAction = form.getAttribute('action') ? sanitizeUrl(form.getAttribute('action')) : '';
    var formMethod = (form.getAttribute('method') || 'POST').toUpperCase();
    var fieldCount = form.querySelectorAll('input:not([type="hidden"]), select, textarea').length || form.querySelectorAll('input, select, textarea').length;

    // ZERO PII : Le contenu et les valeurs des champs (.value) ne sont JAMAIS lus ni envoyés
    sendEvent('form_submit', {
      form_id: formId,
      form_name: formName,
      form_action: formAction,
      form_method: formMethod,
      field_count: fieldCount,
      status: triggerStatus || 'submitted',
      path: window.location.pathname,
      url: sanitizeUrl(window.location.href),
      submitted_at: new Date().toISOString()
    });
  }

  // Écouteur global pour les soumissions HTML natives
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form && form.tagName === 'FORM') {
      trackFormSubmit(form, 'submitted');
    }
  }, { capture: true, passive: true });

  // Prise en charge des soumissions déclenchées par JavaScript (form.submit() et form.requestSubmit())
  if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype) {
    var originalSubmit = HTMLFormElement.prototype.submit;
    if (originalSubmit) {
      HTMLFormElement.prototype.submit = function () {
        try {
          trackFormSubmit(this, 'submitted_via_js');
        } catch (e) {}
        return originalSubmit.apply(this, arguments);
      };
    }

    var originalRequestSubmit = HTMLFormElement.prototype.requestSubmit;
    if (originalRequestSubmit) {
      HTMLFormElement.prototype.requestSubmit = function () {
        try {
          trackFormSubmit(this, 'submitted_via_js');
        } catch (e) {}
        return originalRequestSubmit.apply(this, arguments);
      };
    }
  }

  // 9. Auto-lancement au chargement
  if (autoTrack) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      trackPageView();
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        trackPageView();
      });
    }
  }

  // API Publique window.MetricsDash
  window.MetricsDash = {
    trackPageView: trackPageView,
    trackEvent: function (eventName, data) {
      hasInteraction = true;
      sendEvent(eventName, data);
    },
    trackForm: function (formElement, status) {
      if (formElement) {
        trackFormSubmit(formElement, status || 'manual_track', true);
      }
    }
  };
})();
