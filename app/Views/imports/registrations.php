<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="import-page import-page-stacked">
    <div class="page-header import-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1">Import Registrations</h1>
            <p class="page-subtitle mb-0">Upload file registrasi, pilih mode import tanpa pot atau dengan pot, lalu kelola roster manual dari halaman yang sama.</p>
        </div>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm app-btn">Dashboard</a>
    </div>

    <div class="card import-card">
        <div class="card-header import-card-header">
            <h2 class="h5 mb-0">Upload Registrasi</h2>
        </div>
        <div class="card-body import-card-body">
            <form action="<?= site_url('imports/registrations') ?>" method="post" enctype="multipart/form-data" class="import-upload-form js-import-upload-form" data-default-mode="global">
                <?= csrf_field() ?>

                <div class="row g-3 align-items-start">
                    <div class="col-xl-3 col-lg-4">
                        <label class="form-label">Mode Import</label>
                        <select name="import_mode" class="form-select form-select-sm js-import-mode-select">
                            <option value="global" <?= old('import_mode', 'global') === 'global' ? 'selected' : '' ?>>Tanpa Pot</option>
                            <option value="with_pot" <?= old('import_mode') === 'with_pot' ? 'selected' : '' ?>>Dengan Pot</option>
                        </select>
                        <div class="form-text">Tanpa Pot hanya simpan ke registrasi. Dengan Pot akan sinkron ke team dan member pot terpilih.</div>
                    </div>

                    <div class="col-xl-3 col-lg-4 js-import-pot-group" hidden>
                        <label class="form-label">Tournament</label>
                        <select name="tournament_id" class="form-select form-select-sm js-import-tournament-select">
                            <option value="">Pilih tournament</option>
                            <?php foreach ($tournaments as $tournament): ?>
                                <option value="<?= esc((string) $tournament['id']) ?>" <?= (int) old('tournament_id', $selectedTournamentId) === (int) $tournament['id'] ? 'selected' : '' ?>>
                                    <?= esc($tournament['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-4 js-import-pot-group" hidden>
                        <label class="form-label">Pot</label>
                        <select name="pot_id" class="form-select form-select-sm js-import-pot-select">
                            <option value="">Pilih pot</option>
                            <?php foreach ($pots as $pot): ?>
                                <option value="<?= esc((string) $pot['id']) ?>" <?= (int) old('pot_id', $selectedPotId) === (int) $pot['id'] ? 'selected' : '' ?>>
                                    <?= esc($pot['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-xl-3 col-lg-12">
                        <label class="form-label">File Registrasi</label>
                        <input type="file" class="form-control" name="registration_file" accept=".csv,.txt,.xlsx" required>
                        <div class="form-text">Mendukung CSV, TXT, dan XLSX seperti file form response yang Anda lampirkan.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm app-btn">Import Sekarang</button>
                    <span class="import-inline-note">Preview dan header CSV dihilangkan. Halaman ini fokus ke upload langsung + manual team manager.</span>
                </div>
            </form>
        </div>
    </div>

    <?= view('teams/_manager_workspace', [
        'managerId'            => 'import-manual-manager',
        'managerContext'       => 'import',
        'managerTitle'         => 'Manual Team Manager',
        'managerDescription'   => 'Kelola team dan anggota secara global dari halaman import tanpa panel samping. Klik team di daftar untuk edit cepat.',
        'tournaments'          => $tournaments,
        'pots'                 => $pots,
        'selectedTournamentId' => $selectedTournamentId,
        'selectedPotId'        => $selectedPotId,
        'currentTournamentId'  => $selectedTournamentId,
        'currentPotId'         => $selectedPotId,
        'canManage'            => true,
    ]) ?>

    <div class="card import-card">
        <div class="card-header import-card-header">
            <h2 class="h5 mb-0">Registrasi Terbaru</h2>
        </div>
        <div class="card-body p-0">
            <?php if ($recentRegistrations === []): ?>
                <div class="import-preview-empty">Belum ada registrasi yang tersimpan.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 import-table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Leader</th>
                                <th>WhatsApp</th>
                                <th>Email</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRegistrations as $registration): ?>
                                <tr>
                                    <td class="fw-semibold"><?= esc($registration['team_name']) ?></td>
                                    <td><?= esc($registration['leader_name']) ?></td>
                                    <td><?= esc($registration['whatsapp']) ?></td>
                                    <td><?= esc($registration['email']) ?></td>
                                    <td class="text-muted small"><?= esc((string) ($registration['notes'] ?? '')) ?></td>
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
