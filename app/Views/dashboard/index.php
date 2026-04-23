<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-page">
    <div class="page-header dashboard-page-header">
        <div class="page-title-block"></div>

        <?php if (auth()->user()?->inGroup('admin')): ?>
            <a href="<?= site_url('tournaments/create') ?>" class="btn btn-primary btn-sm app-btn dashboard-create-btn">
                Tambah Tournament
            </a>
        <?php endif; ?>
    </div>

    <div class="dashboard-card card">
        <div class="dashboard-card-header card-header">
            <h2>Tournament</h2>
        </div>

        <div class="dashboard-card-body card-body p-0">
            <?php if ($tournaments === []): ?>
                <div class="dashboard-empty">Belum ada tournament.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dashboard-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama Tournament</th>
                                <th>Status</th>
                                <th class="text-center">Pot</th>
                                <th class="text-center">Team</th>
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
                                    <td class="text-end">
                                        <div class="dashboard-action-group">
                                            <a href="<?= site_url('tournaments/' . $tournament['id'] . '/pots') ?>" class="btn btn-sm btn-primary app-btn">Buka</a>
                                            <a href="<?= site_url('tournaments/edit/' . $tournament['id']) ?>" class="btn btn-sm btn-outline-secondary app-btn">
                                                Edit
                                            </a>
                                            <form action="<?= site_url('tournaments/delete/' . $tournament['id']) ?>" method="post" class="m-0" onsubmit="return confirm('Hapus tournament ini beserta seluruh data turunannya?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="redirect_to" value="<?= esc(current_url()) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger app-btn">Hapus</button>
                                            </form>
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
