<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'Battleground Calc') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= base_url('assets/css/dark-app.css?v=' . (@filemtime(FCPATH . 'assets/css/dark-app.css') ?: time())) ?>" rel="stylesheet">
</head>
<?php
$layoutUser = auth()->user();
$hasGlobalCms = $layoutUser !== null && $layoutUser->inGroup('admin');
?>
<body
    class="app-dark<?= $hasGlobalCms ? ' has-global-cms' : '' ?>"
    data-bg-base-url="<?= esc(site_url('/'), 'attr') ?>"
    data-bg-csrf-token-name="<?= esc(csrf_token(), 'attr') ?>"
    data-bg-csrf-hash="<?= esc(csrf_hash(), 'attr') ?>"
    data-bg-idle-timeout-seconds="300"
    data-bg-idle-warning-seconds="60"
    data-bg-idle-logout-url="<?= esc(site_url('logout'), 'attr') ?>"
    data-bg-keep-alive-url="<?= esc(site_url('session/keep-alive'), 'attr') ?>"
>
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

    <div id="sessionWarningModal" class="session-warning-modal" hidden>
        <div class="session-warning-card" role="dialog" aria-modal="true" aria-labelledby="sessionWarningTitle">
            <div class="session-warning-title" id="sessionWarningTitle">Sesi Akan Berakhir</div>
            <div class="session-warning-body">
                Anda akan logout otomatis dalam <strong class="js-session-warning-countdown">60</strong> detik karena tidak ada aktivitas.
            </div>
            <div class="session-warning-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm js-session-warning-logout">Logout Sekarang</button>
                <button type="button" class="btn btn-primary btn-sm js-session-warning-keepalive">Tetap Login</button>
            </div>
        </div>
    </div>

    <?= $this->include('partials/footer') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= base_url('assets/js/app-shell.js?v=' . (@filemtime(FCPATH . 'assets/js/app-shell.js') ?: time())) ?>"></script>
    <script src="<?= base_url('assets/js/score-workspace.js?v=' . (@filemtime(FCPATH . 'assets/js/score-workspace.js') ?: time())) ?>"></script>
</body>
</html>
