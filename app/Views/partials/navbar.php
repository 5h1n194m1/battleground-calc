<?php
$user    = auth()->user();
$isAdmin = $user !== null && $user->inGroup('admin');

$username = $user?->username ?? $user?->email ?? 'User';
$roleLabel = $isAdmin ? 'Administrator' : 'User';
?>
<nav class="navbar navbar-expand-xl navbar-dark app-topbar sticky-top">
    <div class="container-fluid px-3 px-xl-4">
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-link nav-sidebar-toggle js-global-cms-toggle" aria-controls="globalCmsPanel" aria-expanded="true" aria-label="Toggle global controller">
                    <span class="nav-sidebar-toggle-bars"></span>
                </button>
            <?php endif; ?>

            <a href="<?= site_url('dashboard') ?>" class="navbar-brand app-brand d-flex align-items-center gap-2 m-0 text-decoration-none">
                <span class="app-brand-mark" aria-hidden="true">BC</span>
                <span class="app-brand-copy d-flex flex-column lh-sm">
                    <span class="app-brand-name">Battleground Calc</span>
                    <span class="app-brand-subtitle">Admin scoring workspace</span>
                </span>
            </a>
        </div>

        <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <div class="app-topbar-links ms-xl-4 me-xl-auto mt-3 mt-xl-0">
                <a class="app-nav-link-chip" href="<?= site_url('dashboard') ?>">Dashboard</a>
                <a class="app-nav-link-chip" href="<?= site_url('tournaments') ?>">Tournament</a>
                <a class="app-nav-link-chip" href="<?= site_url('teams') ?>">Teams</a>
                <a class="app-nav-link-chip" href="<?= site_url('leaderboard') ?>">Leaderboard</a>
            </div>

            <div class="d-flex align-items-center gap-2 gap-xl-3 ms-xl-auto mt-3 mt-xl-0">
                <div class="app-user-pill">
                    <div class="app-user-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $username, 0, 1))) ?></div>
                    <div class="app-user-copy">
                        <div class="app-user-name"><?= esc($username) ?></div>
                        <div class="app-user-role"><?= esc($roleLabel) ?></div>
                    </div>
                </div>
                <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm app-btn app-btn-ghost">Logout</a>
            </div>
        </div>
    </div>
</nav>
