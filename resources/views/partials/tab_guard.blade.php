@if(auth()->check() && !auth()->user()->isAdmin() && !auth()->user()->isTabBlocked())
<script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) return;

        const reportUrl = @json(route('tab-violations.store'));
        const userId = {{ (int) auth()->id() }};
        const isJudgePage = {{ request()->routeIs('judge.*') ? 'true' : 'false' }};
        const tabId = (window.crypto && typeof window.crypto.randomUUID === 'function')
            ? window.crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        const channelName = `ob_single_tab_user_${userId}`;
        const storageSignalKey = `ob_tab_signal_${userId}`;

        let isUnloading = false;
        let navigatingUntil = 0;
        let authorizedPdfUntil = 0;
        let viewingAuthorizedPdf = false;
        let violationTriggered = false;
        let lockMessageEl = null;
        let retryButtonEl = null;
        let lastType = null;
        let lastDetails = null;

        const isAuthorizedPdfLink = (anchor) => {
            if (!anchor || anchor.getAttribute('data-allow-new-tab') !== 'true') {
                return false;
            }
            try {
                const url = new URL(anchor.href, window.location.origin);
                return url.origin === window.location.origin
                    && /^\/judge\/problem\/\d+\/pdf$/i.test(url.pathname);
            } catch (e) {
                return false;
            }
        };

        const showBlockOverlay = () => {
            if (lockMessageEl) return;

            document.querySelectorAll('form button, form input, form select, form textarea').forEach((el) => {
                if (el.type !== 'hidden') el.disabled = true;
            });

            const overlay = document.createElement('div');
            overlay.setAttribute('role', 'alertdialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:rgba(15,23,42,.96);color:#fff;text-align:center;';

            const panel = document.createElement('div');
            panel.style.cssText = 'max-width:38rem;padding:2rem;border:1px solid rgba(255,255,255,.2);border-radius:.75rem;background:#1e293b;box-shadow:0 20px 50px rgba(0,0,0,.5);';

            const heading = document.createElement('h1');
            heading.style.cssText = 'margin:0 0 1rem;font-size:1.35rem;color:#fca5a5;';
            heading.textContent = 'Access Blocked due to Browser Tab Policy Violation';

            const message = document.createElement('p');
            message.style.cssText = 'margin:0 0 1.25rem;line-height:1.6;color:#e2e8f0;';
            message.textContent = 'Opening another tab or leaving the authorized window is restricted. Recording violation and locking Lab & Judge access...';

            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-secondary';
            retry.textContent = 'Retry';
            retry.hidden = true;
            retry.addEventListener('click', () => {
                violationTriggered = false;
                reportViolation(lastType, lastDetails);
            });

            panel.append(heading, message, retry);
            overlay.appendChild(panel);
            document.body.appendChild(overlay);

            lockMessageEl = message;
            retryButtonEl = retry;
        };

        const reportViolation = async (violationType, details) => {
            if (violationTriggered) return;
            violationTriggered = true;
            lastType = violationType;
            lastDetails = details;
            showBlockOverlay();

            try {
                const response = await fetch(reportUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        violation_type: violationType,
                        page_url: window.location.pathname,
                        details: details
                    })
                });
                const result = await response.json();
                if (!response.ok || result.blocked !== true) {
                    throw new Error('Server did not confirm block.');
                }

                window.location.reload();
            } catch (error) {
                if (lockMessageEl) {
                    lockMessageEl.textContent = 'Lab & Judge access is locked. Could not confirm delivery with Master PC; please reload or retry.';
                }
                if (retryButtonEl) retryButtonEl.hidden = false;
            }
        };

        // 1. Intercept clicks on links (allow ONLY explicitly authorized Judge PDF links in new tab)
        document.addEventListener('click', (event) => {
            if (violationTriggered) return;
            const anchor = event.target.closest('a[href]');
            if (!anchor) return;

            if (isAuthorizedPdfLink(anchor)) {
                authorizedPdfUntil = Date.now() + 8000;
                return;
            }

            const opensNewTab = event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1
                || anchor.getAttribute('target') === '_blank';

            if (opensNewTab) {
                event.preventDefault();
                event.stopPropagation();
                reportViolation('new_tab_opened', 'Attempted to open an unauthorized link in a new tab.');
                return;
            }

            try {
                const targetUrl = new URL(anchor.href, window.location.origin);
                if (targetUrl.origin === window.location.origin) {
                    navigatingUntil = Date.now() + 5000;
                }
            } catch (e) {}
        }, true);

        // 2. Intercept middle-click (auxclick)
        document.addEventListener('auxclick', (event) => {
            if (violationTriggered || event.button !== 1) return;
            const anchor = event.target.closest('a[href]');
            if (anchor && isAuthorizedPdfLink(anchor)) {
                authorizedPdfUntil = Date.now() + 8000;
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            reportViolation('new_tab_opened', 'Attempted middle-click to open a new browser tab.');
        }, true);

        // 3. Same-tab form submission exemption
        document.addEventListener('submit', () => {
            navigatingUntil = Date.now() + 8000;
        }, true);

        // 4. Intercept new-tab / tab-switch keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            if (violationTriggered || event.repeat) return;
            if (!(event.ctrlKey || event.metaKey)) return;

            const key = (event.key || '').toLowerCase();
            if (key === 't' || key === 'n' || key === 'tab' || key === 'pageup' || key === 'pagedown') {
                event.preventDefault();
                reportViolation('shortcut_new_tab', `Pressed restricted browser tab shortcut (${event.key}).`);
            }
        }, true);

        // 5. Detect browser tab switch / '+' New Tab via visibilitychange
        document.addEventListener('visibilitychange', () => {
            if (violationTriggered) return;

            if (document.visibilityState === 'hidden') {
                if (isUnloading || Date.now() < navigatingUntil) return;
                if (Date.now() < authorizedPdfUntil) {
                    viewingAuthorizedPdf = true;
                    return;
                }
                reportViolation('tab_switched', 'Browser tab hidden after switching tabs or opening a new tab.');
            } else if (document.visibilityState === 'visible') {
                if (viewingAuthorizedPdf) {
                    window.setTimeout(() => {
                        viewingAuthorizedPdf = false;
                        authorizedPdfUntil = 0;
                    }, 200);
                }
            }
        });

        // 6. Strict focus loss on Judge pages (ignoring embedded PDF iframe clicks & authorized PDF tabs)
        if (isJudgePage) {
            window.addEventListener('blur', () => {
                window.setTimeout(() => {
                    if (violationTriggered || isUnloading || Date.now() < navigatingUntil) return;
                    if (viewingAuthorizedPdf || Date.now() < authorizedPdfUntil) return;
                    if (document.activeElement && document.activeElement.tagName === 'IFRAME') return;

                    reportViolation('window_blur', 'Left the Judge window focus during coding.');
                }, 50);
            });
        }

        // 7. Real-time single-tab lock across all OfflineBridge tabs (BroadcastChannel + localStorage fallback)
        let bc = null;
        const handlePeerSignal = (msg) => {
            if (!msg || msg.id === tabId || violationTriggered) return;

            if (msg.type === 'TAB_HELLO') {
                if (isUnloading || Date.now() < navigatingUntil) return;
                if (bc) {
                    bc.postMessage({ type: 'TAB_CONFLICT', id: tabId });
                }
                try {
                    localStorage.setItem(storageSignalKey, JSON.stringify({ type: 'TAB_CONFLICT', id: tabId, ts: Date.now() }));
                } catch (e) {}
                reportViolation('duplicate_tab', 'Opened a second OfflineBridge browser tab simultaneously.');
            } else if (msg.type === 'TAB_CONFLICT') {
                reportViolation('duplicate_tab', 'Another OfflineBridge browser tab is already active.');
            }
        };

        if (typeof BroadcastChannel === 'function') {
            bc = new BroadcastChannel(channelName);
            bc.onmessage = (event) => handlePeerSignal(event.data);
            bc.postMessage({ type: 'TAB_HELLO', id: tabId });
        }

        window.addEventListener('storage', (event) => {
            if (event.key !== storageSignalKey || !event.newValue) return;
            try {
                handlePeerSignal(JSON.parse(event.newValue));
            } catch (e) {}
        });

        try {
            localStorage.setItem(storageSignalKey, JSON.stringify({ type: 'TAB_HELLO', id: tabId, ts: Date.now() }));
        } catch (e) {}

        const markUnloading = () => {
            isUnloading = true;
            if (bc) {
                try { bc.close(); } catch (e) {}
            }
        };
        window.addEventListener('beforeunload', markUnloading);
        window.addEventListener('pagehide', markUnloading);
    })();
</script>
@endif
