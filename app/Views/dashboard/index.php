<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Ringkasan cepat aktivitas turnamen battleground.</p>
    </div>
    <?php if (auth()->user()?->inGroup('admin')): ?>
        <a href="<?= site_url('tournaments') ?>" class="btn btn-primary">Kelola Tournament</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-6 col-lg-4 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2"><?= esc(ucfirst($label)) ?></div>
                    <div class="display-6 fw-semibold"><?= esc((string) $value) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Tournament Terbaru</h2>
            </div>
            <div class="card-body p-0">
                <?php if ($recentTournaments === []): ?>
                    <div class="p-4 text-center text-muted">Belum ada tournament.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTournaments as $tournament): ?>
                                    <tr>
                                        <td><?= esc($tournament['name']) ?></td>
                                        <td><?= esc(date('d M Y H:i', strtotime((string) $tournament['created_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Top Pot by Score</h2>
            </div>
            <div class="card-body p-0">
                <?php if ($topPots === []): ?>
                    <div class="p-4 text-center text-muted">Belum ada score yang masuk.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pot</th>
                                    <th>Tournament</th>
                                    <th class="text-end">Total Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topPots as $pot): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= site_url('leaderboard/pot/' . $pot['id']) ?>" class="text-decoration-none">
                                                <?= esc($pot['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= esc($pot['tournament_name']) ?></td>
                                        <td class="text-end fw-semibold"><?= esc((string) $pot['total_score']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
