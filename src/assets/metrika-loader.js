/**
 * Universal Yandex Metrika Loader (Pure Vanilla JS)
 * High-performance deferred loader designed to eliminate TBT & PSI penalties while preserving 100% analytics fidelity.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
(function(window, document) {
    'use strict';

    if (window.__ymLoaderDefined) {
        return;
    }
    window.__ymLoaderDefined = true;

    /**
     * Initializes a Metrika counter loader with given configuration.
     *
     * @param {Object} options Configuration object
     */
    function initMetrika(options) {
        if (!options || !options.counterId) {
            return;
        }

        var counterId = parseInt(options.counterId, 10);
        if (!counterId || isNaN(counterId)) {
            return;
        }

        // Host filter check
        if (Array.isArray(options.allowedHosts) && options.allowedHosts.length > 0) {
            var currentHost = window.location.hostname.toLowerCase();
            var isAllowed = options.allowedHosts.some(function(h) {
                return h.toLowerCase() === currentHost;
            });
            if (!isAllowed) {
                return;
            }
        }

        var mode = options.mode || 'deferred';
        var tagUrl = options.tagUrl || 'https://mc.yandex.ru/metrika/tag.js';
        var maxDelay = typeof options.maxDelay === 'number' ? options.maxDelay : 3500;
        var idleTimeout = typeof options.idleTimeout === 'number' ? options.idleTimeout : 1500;
        var loadOnInteraction = options.loadOnInteraction !== false;
        var interactionEvents = options.interactionEvents || ['pointerdown', 'touchstart', 'scroll', 'keydown', 'wheel'];
        var initOptions = options.initOptions || {};
        var nonce = options.nonce || '';
        var requireConsent = Boolean(options.requireConsent);
        var consentStorageKey = options.consentStorageKey || null;
        var consentEvent = options.consentEvent || 'cookie_analytics_enabled';
        var rejectEvent = options.rejectEvent || 'cookie_analytics_disabled';

        // 1. Establish early window.ym queue immediately
        window.ym = window.ym || function() {
            (window.ym.a = window.ym.a || []).push(arguments);
        };
        window.ym.l = window.ym.l || Date.now();

        // 2. Backward compatibility proxy for legacy yaCounter<ID>
        var yaCounterKey = 'yaCounter' + counterId;
        if (!window[yaCounterKey]) {
            window[yaCounterKey] = {
                reachGoal: function(target, params, callback, ctx) {
                    window.ym(counterId, 'reachGoal', target, params, callback, ctx);
                },
                userParams: function(params) {
                    window.ym(counterId, 'userParams', params);
                },
                params: function(params) {
                    window.ym(counterId, 'params', params);
                },
                hit: function(url, hitOptions) {
                    window.ym(counterId, 'hit', url, hitOptions);
                }
            };
        }

        // 3. Queue Metrika init call early so any goal tracking preserves init state
        window.ym(counterId, 'init', initOptions);

        // State flags
        window.__ymLoadedCounters = window.__ymLoadedCounters || {};
        var isScriptInjected = false;
        var maxTimerId = null;
        var idleCallbackId = null;
        var listenersAttached = false;

        /**
         * Cleans up timers and interaction listeners
         */
        function cleanup() {
            if (maxTimerId) {
                clearTimeout(maxTimerId);
                maxTimerId = null;
            }
            if (idleCallbackId && 'cancelIdleCallback' in window) {
                window.cancelIdleCallback(idleCallbackId);
                idleCallbackId = null;
            }
            if (listenersAttached) {
                interactionEvents.forEach(function(evt) {
                    window.removeEventListener(evt, triggerLoad);
                    document.removeEventListener(evt, triggerLoad);
                });
                window.removeEventListener('mousemove', triggerLoad);
                listenersAttached = false;
            }
        }

        /**
         * Injects tag.js into the DOM once
         */
        function injectScript() {
            if (isScriptInjected || window.__ymLoadedCounters[counterId]) {
                cleanup();
                return;
            }

            // Also check DOM in case another script already loaded it
            var existing = document.querySelector('script[data-ym-loader="' + counterId + '"]') ||
                           document.querySelector('script[src*="mc.yandex.ru/metrika/tag.js"]');
            if (existing) {
                isScriptInjected = true;
                window.__ymLoadedCounters[counterId] = true;
                cleanup();
                return;
            }

            isScriptInjected = true;
            window.__ymLoadedCounters[counterId] = true;
            cleanup();

            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = tagUrl;
            script.dataset.ymLoader = String(counterId);

            if (nonce) {
                script.nonce = nonce;
            }

            if ('fetchPriority' in HTMLScriptElement.prototype) {
                script.fetchPriority = 'low';
            }

            script.onload = function() {
                try {
                    window.dispatchEvent(new CustomEvent('ym_loaded', {
                        detail: { counterId: counterId, mode: mode }
                    }));
                } catch (e) {}
                if (typeof options.onLoad === 'function') {
                    try {
                        options.onLoad(counterId);
                    } catch (e) {}
                }
            };

            var firstScript = document.getElementsByTagName('script')[0];
            if (firstScript && firstScript.parentNode) {
                firstScript.parentNode.insertBefore(script, firstScript);
            } else {
                (document.head || document.documentElement).appendChild(script);
            }
        }

        /**
         * Trigger function called on interaction or timer
         */
        function triggerLoad() {
            injectScript();
        }

        // Global manual trigger
        window.loadYandexMetrika = function(forceCounterId) {
            if (!forceCounterId || forceCounterId === counterId) {
                injectScript();
            }
        };

        window.addEventListener('ym_load_now', function(e) {
            if (!e.detail || !e.detail.counterId || e.detail.counterId === counterId) {
                injectScript();
            }
        });

        // Mode 1: Immediate mode
        if (mode === 'immediate') {
            injectScript();
            return;
        }

        // Mode 2: Deferred mode with Consent check
        function checkConsent() {
            if (!requireConsent) {
                return true;
            }
            if (consentStorageKey) {
                try {
                    var raw = localStorage.getItem(consentStorageKey);
                    if (raw) {
                        var parsed = JSON.parse(raw);
                        if (parsed && (parsed.analytics === true || parsed.analytics === 1)) {
                            return true;
                        }
                        if (parsed && (parsed.analytics === false || parsed.analytics === 0)) {
                            return false;
                        }
                    }
                } catch (e) {}
            }
            return null; // undecided
        }

        var consentStatus = checkConsent();

        if (consentStatus === false) {
            // Explicitly rejected. Listen if user changes settings later.
            window.addEventListener(consentEvent, function() {
                scheduleDeferredLoad();
            }, { once: true });
            return;
        }

        if (consentStatus === null) {
            // Awaiting consent choice
            var onConsentGranted = function() {
                window.removeEventListener(rejectEvent, onConsentRejected);
                scheduleDeferredLoad();
            };
            var onConsentRejected = function() {
                window.removeEventListener(consentEvent, onConsentGranted);
                cleanup();
            };
            window.addEventListener(consentEvent, onConsentGranted, { once: true });
            window.addEventListener(rejectEvent, onConsentRejected, { once: true });
            return;
        }

        // Consent granted or not required -> schedule deferred load
        scheduleDeferredLoad();

        function scheduleDeferredLoad() {
            var listenerOptions = { passive: true, once: true };

            // 1. Interaction listeners
            if (loadOnInteraction) {
                listenersAttached = true;
                interactionEvents.forEach(function(evt) {
                    window.addEventListener(evt, triggerLoad, listenerOptions);
                    document.addEventListener(evt, triggerLoad, listenerOptions);
                });
                // Small delay before listening to mousemove to avoid accidental trigger during initial render
                setTimeout(function() {
                    if (listenersAttached && !isScriptInjected) {
                        window.addEventListener('mousemove', triggerLoad, listenerOptions);
                    }
                }, 300);
            }

            // 2. Maximum delay safety fallback
            if (maxDelay > 0) {
                maxTimerId = setTimeout(triggerLoad, maxDelay);
            }

            // 3. Idle / double RAF scheduling
            var runIdle = function() {
                if ('requestIdleCallback' in window) {
                    idleCallbackId = window.requestIdleCallback(triggerLoad, { timeout: idleTimeout });
                } else {
                    setTimeout(triggerLoad, 50);
                }
            };

            var scheduleAfterRender = function() {
                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(function() {
                        window.requestAnimationFrame(runIdle);
                    });
                } else {
                    runIdle();
                }
            };

            if (document.readyState === 'complete') {
                scheduleAfterRender();
            } else {
                window.addEventListener('load', scheduleAfterRender, { once: true });
            }
        }
    }

    // Expose loader to window
    window.initMetrikaLoader = initMetrika;

})(window, document);
