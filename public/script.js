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

  var scriptSrc = currentScript.src || '';
  var scriptOrigin = scriptSrc ? new URL(scriptSrc).origin : window.location.origin;
  var customHost = currentScript.getAttribute('data-host-url');
  var hostUrl = customHost ? customHost.replace(/\/$/, '') : scriptOrigin;
  var ingestUrl = hostUrl + '/api/v1/telemetry/sdk';

  var autoTrack = currentScript.getAttribute('data-auto-track') !== 'false';
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
        var blob = new Blob([payloadStr], { type: 'text/plain;charset=UTF-8' });
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

  // 8. Auto-lancement au chargement
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
    }
  };
})();
