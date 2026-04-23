<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$status = $tournament['status'] ?? 'belum_mulai';
$statusLabel = $statusOptions[$status] ?? ucwords(str_replace('_', ' ', $status));
$badgeClass = match ($status) {
    'start' => 'text-bg-success',
    'selesai' => 'text-bg-secondary',
    default => 'text-bg-warning',
};
?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1"><?= esc($tournament['name']) ?></h1>
        
    </div>
    <div class="d-flex gap-2">
        <span class="badge <?= esc($badgeClass) ?> d-inline-flex align-items-center px-3"><?= esc($statusLabel) ?></span>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Kembali</a>
        <a href="<?= site_url('tournaments/edit/' . $tournament['id']) ?>" class="btn btn-outline-dark">Edit Event</a>
    </div>
</div>

<div class="card stat-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Tambah Pot</h2>
            </div>
            <div class="small text-muted"><?= esc((string) count($pots)) ?> pot</div>
        </div>
        <?= view('pots/form', [
            'action'      => site_url('pots/store'),
            'pot'         => null,
            'tournament'  => $tournament,
            'submitLabel' => 'Tambah Pot',
        ]) ?>
    </div>
</div>

<div class="mt-4">
    <?php if ($pots === []): ?>
        <div class="card stat-card">
            <div class="card-body text-center text-muted py-5">Belum ada pot.</div>
        </div>
    <?php else: ?>
        <?php foreach ($pots as $pot): ?>
            <?= view('pots/_manager', [
                'pot'          => $pot,
                'tournament'   => $tournament,
                'placementMap' => $placementMap,
            ]) ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
