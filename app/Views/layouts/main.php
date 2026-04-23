<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'Battleground Calc') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/dark-app.css?v=' . (@filemtime(FCPATH . 'assets/css/dark-app.css') ?: time())) ?>" rel="stylesheet">
</head>
<?php
$layoutUser = auth()->user();
$hasGlobalCms = $layoutUser !== null && $layoutUser->inGroup('admin');
?>
<body class="app-dark<?= $hasGlobalCms ? ' has-global-cms' : '' ?>">
    <?= $this->include('partials/navbar') ?>
    <?php if ($hasGlobalCms): ?>
        <?= $this->include('partials/global_cms') ?>
    <?php endif; ?>

    <main class="app-main py-3">
        <div class="container-fluid app-container px-3 px-xl-4">
            <div id="ajaxFlashHost" class="app-flash-host"></div>
            <?= $this->renderSection('content') ?>
        </div>
    </main>

    <?= $this->include('partials/footer') ?>

    <script>
        window.BGApp = {
            csrfTokenName: '<?= esc(csrf_token()) ?>',
            csrfHash: '<?= esc(csrf_hash()) ?>',
            baseUrl: '<?= site_url('/') ?>',
            idleTimeoutSeconds: 300,
            idleLogoutUrl: '<?= site_url('logout') ?>',
            idleTimer: null,

            resetIdleTimer() {
                if (!this.idleTimeoutSeconds || !this.idleLogoutUrl) {
                    return;
                }

                window.clearTimeout(this.idleTimer);
                this.idleTimer = window.setTimeout(() => {
                    window.location.href = this.idleLogoutUrl;
                }, this.idleTimeoutSeconds * 1000);
            },

            startIdleWatcher() {
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

            updateCsrf(tokenName, tokenHash) {
                if (!tokenName || !tokenHash) return;
                this.csrfTokenName = tokenName;
                this.csrfHash = tokenHash;
                this.syncAllPostForms();
            },

            initGlobalCms() {
                const body = document.body;
                const sidebar = document.getElementById('globalCmsPanel');
                const toggleButtons = Array.from(document.querySelectorAll('.js-global-cms-toggle'));
                const backdrop = document.querySelector('.js-global-cms-backdrop');
                const searchInput = document.querySelector('.js-global-cms-search');
                const items = Array.from(document.querySelectorAll('[data-global-cms-item]'));
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

                const runFilter = () => {
                    const keyword = (searchInput?.value || '').trim().toLowerCase();
                    items.forEach((item) => {
                        const haystack = String(item.getAttribute('data-search-text') || '').toLowerCase();
                        item.hidden = keyword !== '' && !haystack.includes(keyword);
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
                runFilter();

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

                backdrop?.addEventListener('click', () => {
                    body.classList.remove('app-global-cms-mobile-open');
                    syncToggleState();
                });

                searchInput?.addEventListener('input', runFilter);

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
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            window.BGApp.syncAllPostForms();
            window.BGApp.startIdleWatcher();
            window.BGApp.initGlobalCms();

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

            const method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'post') {
                window.BGApp.ensureFormCsrf(form);
            }
        }, true);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/score-workspace.js?v=' . (@filemtime(FCPATH . 'assets/js/score-workspace.js') ?: time())) ?>"></script>
</body>
</html>
