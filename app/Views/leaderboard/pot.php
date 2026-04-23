<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Leaderboard Pot</h1>
        <p class="text-muted mb-0"><?= esc($pot['tournament_name']) ?> / <?= esc($pot['name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (auth()->user()?->inGroup('admin')): ?>
            <a href="<?= site_url('pots/' . $pot['id'] . '/scores') ?>" class="btn btn-outline-success">Score</a>
        <?php endif; ?>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<?= $this->include('leaderboard/_table') ?>
<?= $this->endSection() ?>
