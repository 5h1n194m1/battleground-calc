<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-page">
    <div class="page-header dashboard-page-header">
        <div class="page-title-block">
            <p class="page-subtitle dashboard-hero-subtitle mb-0">
                Pilih tournament yang ada di database untuk langsung masuk ke halaman kelola.
            </p>
        </div>

        <?php if (auth()->user()?->inGroup('admin')): ?>
            <a href="<?= site_url('tournaments/create') ?>" class="btn btn-primary btn-sm app-btn dashboard-create-btn">
                Tambah Tournament
            </a>
        <?php endif; ?>
    </div>

    <div class="dashboard-card card">
        <div class="dashboard-card-header card-header">
            <h2>Management Event</h2>
        </div>

        <div class="dashboard-card-body card-body p-0">
            <?php if ($tournaments === []): ?>
                <div class="dashboard-empty">Belum ada tournament di database.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama Tournament</th>
                                <th>Status</th>
                                <th class="text-center">Pot</th>
                                <th class="text-center">Team</th>
                                <th>Keterangan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournaments as $tournament): ?>
                                <?php
                                $status = $tournament['status'] ?? 'belum_mulai';
                                $statusLabel = $statusOptions[$status] ?? ucwords(str_replace('_', ' ', $status));
                                $badgeClass = match ($status) {
                                    'start' => 'text-bg-success',
                                    'selesai' => 'text-bg-secondary',
                                    default => 'text-bg-warning',
                                };
                                $description = match ($status) {
                                    'start' => 'Tournament sedang berjalan dan siap dikelola.',
                                    'selesai' => 'Tournament sudah selesai. Data masih bisa dilihat sebagai referensi.',
                                    default => 'Tournament belum dimulai. Silakan siapkan pot dan team terlebih dahulu.',
                                };
                                ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="<?= site_url('tournaments/' . $tournament['id'] . '/pots') ?>" class="dashboard-link">
                                            <?= esc($tournament['name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?= esc($badgeClass) ?>"><?= esc($statusLabel) ?></span>
                                    </td>
                                    <td class="text-center"><?= esc((string) $tournament['pot_count']) ?></td>
                                    <td class="text-center"><?= esc((string) $tournament['team_count']) ?></td>
                                    <td class="dashboard-desc"><?= esc($description) ?></td>
                                    <td class="text-end">
                                        <div class="dashboard-action-group">
                                            <a href="<?= site_url('tournaments/' . $tournament['id'] . '/pots') ?>" class="btn btn-sm btn-primary app-btn">
                                                <?= $status === 'selesai' ? 'Lihat' : 'Kelola' ?>
                                            </a>
                                            <a href="<?= site_url('tournaments/edit/' . $tournament['id']) ?>" class="btn btn-sm btn-outline-secondary app-btn">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>