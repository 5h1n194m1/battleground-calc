(function () {
    const body = document.body;
    if (!body) {
        return;
    }

    const config = {
        csrfTokenName: body.dataset.bgCsrfTokenName || '',
        csrfHash: body.dataset.bgCsrfHash || '',
        baseUrl: body.dataset.bgBaseUrl || '',
        idleTimeoutSeconds: Number(body.dataset.bgIdleTimeoutSeconds || 0),
        idleWarningSeconds: Number(body.dataset.bgIdleWarningSeconds || 0),
        idleLogoutUrl: body.dataset.bgIdleLogoutUrl || '',
        keepAliveUrl: body.dataset.bgKeepAliveUrl || '',
    };

    const BGApp = {
        ...config,
        idleTimer: null,
        idleWarningTimer: null,
        idleCountdownTimer: null,
        idleDeadlineAt: 0,

        resetIdleTimer() {
            if (!this.idleTimeoutSeconds || !this.idleLogoutUrl) {
                return;
            }

            if (this.idleWarningTimer) {
                window.clearTimeout(this.idleWarningTimer);
            }

            if (this.idleCountdownTimer) {
                window.clearInterval(this.idleCountdownTimer);
                this.idleCountdownTimer = null;
            }

            this.hideSessionWarning();
            this.idleDeadlineAt = Date.now() + (this.idleTimeoutSeconds * 1000);
            window.clearTimeout(this.idleTimer);
            this.idleTimer = window.setTimeout(() => {
                window.location.href = this.idleLogoutUrl;
            }, this.idleTimeoutSeconds * 1000);

            const warningDelay = Math.max(0, (this.idleTimeoutSeconds - this.idleWarningSeconds) * 1000);
            this.idleWarningTimer = window.setTimeout(() => {
                this.showSessionWarning();
            }, warningDelay);
        },

        startIdleWatcher() {
            if (!this.idleTimeoutSeconds) {
                return;
            }

            const activityEvents = ['mousedown', 'keydown', 'mousemove', 'scroll', 'touchstart'];
            const reset = () => this.resetIdleTimer();

            activityEvents.forEach((eventName) => {
                window.addEventListener(eventName, reset, { passive: true });
            });

            this.resetIdleTimer();
        },

        ensureFormCsrf(form) {
            if (!form || !this.csrfTokenName || !this.csrfHash) {
                return;
            }

            let input = form.querySelector(`input[name="${this.csrfTokenName}"]`);
            if (!input) {
                form.querySelectorAll('input[type="hidden"]').forEach((hidden) => {
                    if (/csrf/i.test(hidden.name || '')) {
                        hidden.remove();
                    }
                });

                input = document.createElement('input');
                input.type = 'hidden';
                input.name = this.csrfTokenName;
                form.prepend(input);
            }

            input.value = this.csrfHash;
        },

        syncAllPostForms() {
            document.querySelectorAll('form').forEach((form) => {
                const method = (form.getAttribute('method') || 'get').toLowerCase();
                if (method === 'post') {
                    this.ensureFormCsrf(form);
                }
            });
        },

        showFlash() {
            return;
        },

        showSessionWarning() {
            const modal = document.getElementById('sessionWarningModal');
            if (!modal) {
                return;
            }

            const countdownEl = modal.querySelector('.js-session-warning-countdown');
            modal.hidden = false;

            const renderCountdown = () => {
                const seconds = Math.max(0, Math.ceil((this.idleDeadlineAt - Date.now()) / 1000));
                if (countdownEl) {
                    countdownEl.textContent = String(seconds);
                }
            };

            renderCountdown();
            if (this.idleCountdownTimer) {
                window.clearInterval(this.idleCountdownTimer);
            }

            this.idleCountdownTimer = window.setInterval(renderCountdown, 1000);
        },

        hideSessionWarning() {
            const modal = document.getElementById('sessionWarningModal');
            if (modal) {
                modal.hidden = true;
            }
        },

        async keepSessionAlive() {
            if (!this.keepAliveUrl) {
                this.resetIdleTimer();
                return;
            }

            const response = await fetch(this.keepAliveUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Sesi sudah berakhir.');
            }

            const payload = await response.json();
            if (payload && payload.csrfTokenName && payload.csrfHash) {
                this.updateCsrf(payload.csrfTokenName, payload.csrfHash);
            }

            this.resetIdleTimer();
        },

        updateCsrf(tokenName, tokenHash) {
            if (!tokenName || !tokenHash) {
                return;
            }

            this.csrfTokenName = tokenName;
            this.csrfHash = tokenHash;
            this.syncAllPostForms();
        },

        initGlobalCms() {
            const sidebar = document.getElementById('globalCmsPanel');
            const toggleButtons = Array.from(document.querySelectorAll('.js-global-cms-toggle'));
            const backdrop = document.querySelector('.js-global-cms-backdrop');
            const collapseKey = 'bgcalc-global-cms-collapsed';

            if (!sidebar || toggleButtons.length === 0) {
                return;
            }

            const syncToggleState = () => {
                const expanded = !(body.classList.contains('app-global-cms-collapsed') || body.classList.contains('app-global-cms-mobile-open'));
                toggleButtons.forEach((button) => {
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            };

            try {
                if (window.innerWidth >= 1200 && sessionStorage.getItem(collapseKey) === '1') {
                    body.classList.add('app-global-cms-collapsed');
                }
            } catch (error) {
                // ignore
            }

            syncToggleState();

            const toggleSidebar = () => {
                if (window.innerWidth >= 1200) {
                    body.classList.toggle('app-global-cms-collapsed');

                    try {
                        sessionStorage.setItem(collapseKey, body.classList.contains('app-global-cms-collapsed') ? '1' : '0');
                    } catch (error) {
                        // ignore
                    }
                } else {
                    body.classList.toggle('app-global-cms-mobile-open');
                }

                syncToggleState();
            };

            toggleButtons.forEach((button) => {
                button.addEventListener('click', toggleSidebar);
            });

            if (backdrop) {
                backdrop.addEventListener('click', () => {
                    body.classList.remove('app-global-cms-mobile-open');
                    syncToggleState();
                });
            }

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1200) {
                    body.classList.remove('app-global-cms-mobile-open');
                }
                syncToggleState();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    body.classList.remove('app-global-cms-mobile-open');
                    syncToggleState();
                }
            });
        },
    };

    window.BGApp = BGApp;

    document.addEventListener('DOMContentLoaded', () => {
        BGApp.syncAllPostForms();
        BGApp.startIdleWatcher();
        BGApp.initGlobalCms();

        const warningModal = document.getElementById('sessionWarningModal');
        const keepaliveButton = warningModal ? warningModal.querySelector('.js-session-warning-keepalive') : null;
        const logoutButton = warningModal ? warningModal.querySelector('.js-session-warning-logout') : null;

        if (keepaliveButton) {
            keepaliveButton.addEventListener('click', async () => {
                try {
                    await BGApp.keepSessionAlive();
                } catch (error) {
                    window.location.href = BGApp.idleLogoutUrl;
                }
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener('click', () => {
                window.location.href = BGApp.idleLogoutUrl;
            });
        }

        const scrollKey = 'bgcalc-scroll:' + window.location.pathname + window.location.search;
        const savedScroll = window.sessionStorage.getItem(scrollKey);
        if (savedScroll !== null) {
            window.requestAnimationFrame(() => {
                window.scrollTo({ top: Number(savedScroll) || 0, left: 0, behavior: 'instant' });
                window.sessionStorage.removeItem(scrollKey);
            });
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.getAttribute('data-confirm');
        if (message && window.confirm(message) === false) {
            event.preventDefault();
            return;
        }

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'post') {
            BGApp.ensureFormCsrf(form);
        }
    }, true);

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-confirm-click]') : null;
        if (!button) {
            return;
        }

        const message = button.getAttribute('data-confirm-click') || 'Lanjutkan aksi ini?';
        if (window.confirm(message) === false) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);
})();
