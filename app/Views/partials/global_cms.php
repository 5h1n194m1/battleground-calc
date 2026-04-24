<?php

$user = auth()->user();
$isAdmin = $user !== null && $user->inGroup('admin');

if (! $isAdmin) {
    return;
}

$request = service('request');
$segments = $request->getUri()->getSegments();
$segment1 = trim((string) ($segments[0] ?? ''));
$currentPath = trim($request->getUri()->getPath(), '/');

$currentContext = [
    'title' => 'Control Center',
    'meta' => 'Pilih modul kerja utama dari sidebar.',
];

if ($segment1 === 'imports') {
    $currentContext = [
        'title' => 'Import Teams',
        'meta' => 'Kelola upload roster dan penempatan awal team.',
    ];
} elseif ($segment1 === 'teams') {
    $currentContext = [
        'title' => 'Team Roster',
        'meta' => 'Rapikan nama team, anggota, tournament, dan pot.',
    ];
} elseif ($segment1 === 'pots') {
    $currentContext = [
        'title' => 'Live Score',
        'meta' => 'Input score, atur pot, dan buka export tournament aktif.',
    ];
} elseif ($segment1 === 'dashboard' || $segment1 === 'tournaments' || $segment1 === '') {
    $currentContext = [
        'title' => 'Management Event',
        'meta' => 'Buka tournament, atur status, dan susun pot.',
    ];
}

$quickLinks = [
    [
        'label' => 'Management Event',
        'href' => site_url('dashboard'),
        'icon' => 'ME',
        'description' => 'Tournament, status, dan pot',
        'active' => $segment1 === '' || $segment1 === 'dashboard' || $segment1 === 'tournaments',
    ],
    [
        'label' => 'Import Teams',
        'href' => site_url('imports/teams'),
        'icon' => 'IM',
        'description' => 'Upload roster ke sistem',
        'active' => $segment1 === 'imports',
    ],
    [
        'label' => 'Teams',
        'href' => site_url('teams/roster'),
        'icon' => 'TM',
        'description' => 'Roster global dan maintenance',
        'active' => $segment1 === 'teams',
    ],
];
?>
<div class="app-global-cms-backdrop js-global-cms-backdrop"></div>
<aside class="app-global-cms-sidebar" id="globalCmsPanel">
    <div class="app-global-cms-brand">
        <a href="<?= site_url('dashboard') ?>" class="app-global-cms-brand-link">
            <span class="app-global-cms-brand-badge">BC</span>
            <span class="app-global-cms-brand-copy">
                <span class="app-global-cms-brand-title">Battleground Calc</span>
                <span class="app-global-cms-brand-subtitle">Admin scoring workspace</span>
            </span>
        </a>
    </div>

    <div class="app-global-cms-user">
        <div class="app-global-cms-user-avatar"><?= esc(strtoupper(substr((string) ($user?->username ?? $user?->email ?? 'A'), 0, 1))) ?></div>
        <div class="app-global-cms-user-copy">
            <div class="app-global-cms-user-name"><?= esc($user?->username ?? $user?->email ?? 'Admin') ?></div>
            <div class="app-global-cms-user-role">Administrator</div>
        </div>
    </div>

    <div class="app-global-cms-search-wrap">
        <input type="text" class="form-control form-control-sm js-global-cms-search" placeholder="Cari menu cepat...">
    </div>

    <div class="app-global-cms-body">
        <div class="app-global-cms-section">
            <div class="app-global-cms-section-label">Menu Utama</div>
            <nav class="app-global-cms-nav">
                <?php foreach ($quickLinks as $link): ?>
                    <a
                        href="<?= esc($link['href']) ?>"
                        class="app-global-cms-nav-link<?= $link['active'] ? ' is-active' : '' ?>"
                        data-global-cms-item
                        data-search-text="<?= esc(strtolower($link['label'])) ?>"
                    >
                        <span class="app-global-cms-nav-icon"><?= esc($link['icon']) ?></span>
                        <span class="app-global-cms-nav-copy">
                            <span class="app-global-cms-nav-text"><?= esc($link['label']) ?></span>
                            <span class="app-global-cms-nav-meta"><?= esc($link['description']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="app-global-cms-section">
            <div class="app-global-cms-section-label">Context</div>
            <div class="app-global-cms-context-card" data-global-cms-item data-search-text="<?= esc(strtolower($currentContext['title'] . ' ' . $currentContext['meta'] . ' ' . $currentPath)) ?>">
                <div class="app-global-cms-context-eyebrow">Halaman aktif</div>
                <div class="app-global-cms-context-title"><?= esc($currentContext['title']) ?></div>
                <div class="app-global-cms-context-meta"><?= esc($currentContext['meta']) ?></div>
                <div class="app-global-cms-context-path"><?= esc($currentPath !== '' ? '/' . $currentPath : '/dashboard') ?></div>
            </div>
        </div>
    </div>
</aside>
