<?php
$user    = auth()->user();
$isAdmin = $user !== null && $user->inGroup('admin');
?>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark shadow-sm">
    <div class="container">
        <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-link nav-sidebar-toggle js-global-cms-toggle" aria-controls="globalCmsPanel" aria-expanded="true">
                <span class="nav-sidebar-toggle-bars"></span>
            </button>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <div class="d-flex align-items-center gap-3 text-white ms-auto">
                <div class="small">
                    <div class="fw-semibold"><?= esc($user?->username ?? $user?->email ?? 'User') ?></div>
                    <div class="text-white-50"><?= $isAdmin ? 'Admin' : 'User' ?></div>
                </div>
                <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>
