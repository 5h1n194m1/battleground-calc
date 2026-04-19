<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Pot Tournament</h1>
        <p class="text-muted mb-0"><?= esc($tournament['name']) ?></p>
    </div>
    <a href="<?= site_url('tournaments') ?>" class="btn btn-outline-secondary">Kembali ke Tournament</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Tambah Pot</h2>
            </div>
            <div class="card-body">
                <?= view('pots/form', [
                    'action'      => site_url('pots/store'),
                    'pot'         => null,
                    'tournament'  => $tournament,
                    'submitLabel' => 'Tambah Pot',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?= $this->include('pots/_table') ?>
    </div>
</div>
<?= $this->endSection() ?>
