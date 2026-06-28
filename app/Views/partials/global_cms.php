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

$quickLinks = [
    [
        'label' => 'Management Event',
        'href' => site_url('dashboard'),
        'icon' => 'ME',
        'active' => $segment1 === '' || $segment1 === 'dashboard' || $segment1 === 'tournaments',
    ],
    [
        'label' => 'Import Teams',
        'href' => site_url('imports/teams'),
        'icon' => 'IM',
        'active' => $segment1 === 'imports',
    ],
    [
        'label' => 'Teams',
        'href' => site_url('teams/roster'),
        'icon' => 'TM',
        'active' => $segment1 === 'teams',
    ],
];
?>
<div class="app-global-cms-backdrop js-global-cms-backdrop"></div>
<aside class="app-global-cms-sidebar" id="globalCmsPanel" aria-label="Global controller">
    <div class="app-global-cms-brand">
        <a href="<?= site_url('dashboard') ?>" class="app-global-cms-brand-link">
            <span class="app-global-cms-brand-badge">BC</span>
            <span class="app-global-cms-brand-copy">
                <span class="app-global-cms-brand-title">Battleground Calc</span>
                <span class="app-global-cms-brand-subtitle">Admin workspace</span>
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

    <div class="app-global-cms-body">
        <div class="app-global-cms-section">
            <nav class="app-global-cms-nav">
                <?php foreach ($quickLinks as $link): ?>
                    <a
                        href="<?= esc($link['href']) ?>"
                        class="app-global-cms-nav-link<?= $link['active'] ? ' is-active' : '' ?>"
                        data-global-cms-item
                        data-search-text="<?= esc(strtolower($link['label'])) ?>">
                        <span class="app-global-cms-nav-icon"><?= esc($link['icon']) ?></span>
                        <span class="app-global-cms-nav-copy">
                            <span class="app-global-cms-nav-text"><?= esc($link['label']) ?></span>

                            <?php if (! empty($link['description'])): ?>
                                <span class="app-global-cms-nav-meta">
                                    <?= esc($link['description']) ?>
                                </span>
                            <?php endif; ?>

                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</aside>