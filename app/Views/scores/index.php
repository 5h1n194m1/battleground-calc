<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Input Score</h1>
        <p class="text-muted mb-0"><?= esc($pot['tournament_name']) ?> / <?= esc($pot['name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('leaderboard/pot/' . $pot['id']) ?>" class="btn btn-outline-dark">Leaderboard</a>
        <a href="<?= site_url('pots/' . $pot['id'] . '/teams') ?>" class="btn btn-outline-secondary">Kelola Team</a>
    </div>
</div>

<?= $this->include('scores/_filters') ?>
<?= $this->include('scores/_table') ?>
<?= $this->endSection() ?>
