<?php
$user    = auth()->user();
$isAdmin = $user !== null && $user->inGroup('admin');
$path    = trim(service('request')->getUri()->getPath(), '/');
?>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('dashboard') ?>">Battleground Calc</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $path === '' || $path === 'dashboard' ? 'active' : '' ?>" href="<?= site_url('dashboard') ?>">Dashboard</a>
                </li>
                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($path, 'tournaments') ? 'active' : '' ?>" href="<?= site_url('tournaments') ?>">Tournament</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_starts_with($path, 'imports/registrations') ? 'active' : '' ?>" href="<?= site_url('imports/registrations') ?>">Import Registrations</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3 text-white">
                <div class="small">
                    <div class="fw-semibold"><?= esc($user?->username ?? $user?->email ?? 'User') ?></div>
                    <div class="text-white-50"><?= $isAdmin ? 'Admin' : 'User' ?></div>
                </div>
                <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>
