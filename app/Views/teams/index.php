<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Team Pot</h1>
        <p class="text-muted mb-0"><?= esc($pot['tournament_name']) ?> / <?= esc($pot['name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('leaderboard/pot/' . $pot['id']) ?>" class="btn btn-outline-dark">Leaderboard</a>
        <a href="<?= site_url('tournaments/' . $pot['tournament_id'] . '/pots') ?>" class="btn btn-outline-secondary">Kembali ke Pot</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Tambah Team</h2>
            </div>
            <div class="card-body">
                <?= view('teams/form', [
                    'action'      => site_url('teams/store'),
                    'team'        => null,
                    'pot'         => $pot,
                    'submitLabel' => 'Tambah Team',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?= $this->include('teams/_table') ?>
    </div>
</div>
<?= $this->endSection() ?>
