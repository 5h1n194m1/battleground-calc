<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="import-page import-page-stacked import-page-ergonomic import-page-compact">
    <div class="page-header import-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1">Import Teams</h1>
        </div>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm app-btn">Kembali</a>
    </div>

    <div class="import-workspace-grid">
        <div class="card import-card import-control-card">
            <div class="card-header import-card-header">
                <h2 class="h5 mb-0">Upload</h2>
            </div>
            <div class="card-body import-card-body">
                <form action="<?= site_url('imports/teams') ?>" method="post" enctype="multipart/form-data" class="import-upload-form js-import-upload-form" data-default-mode="global">
                    <?= csrf_field() ?>

                    <div class="row g-2 align-items-start import-upload-grid import-upload-grid-compact">
                        <div class="col-12">
                            <label class="form-label">Tournament</label>
                            <select name="tournament_id" class="form-select form-select-sm js-import-page-tournament-select">
                                <option value="">Pilih tournament</option>
                                <?php foreach ($tournaments as $tournament): ?>
                                    <option value="<?= esc((string) $tournament['id']) ?>" <?= (int) old('tournament_id', $selectedTournamentId) === (int) $tournament['id'] ? 'selected' : '' ?>>
                                        <?= esc($tournament['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Pilih tournament dulu, lalu pengaturan sisanya cukup lewat team manager.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Penempatan Awal</label>
                            <select name="import_mode" class="form-select form-select-sm js-import-mode-select">
                                <option value="global" <?= old('import_mode', 'global') === 'global' ? 'selected' : '' ?>>Belum Masuk Pot</option>
                                <option value="with_pot" <?= old('import_mode') === 'with_pot' ? 'selected' : '' ?>>Langsung ke Pot</option>
                            </select>
                        </div>

                        <div class="col-md-6 js-import-pot-group" hidden>
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

                        <div class="col-12">
                            <label class="form-label">File</label>
                            <input type="file" class="form-control" name="team_file" accept=".csv,.txt,.xlsx" required>
                        </div>
                    </div>

                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary btn-sm app-btn">Import</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="import-manager-panel">
            <?= view('teams/_manager_workspace', [
                'managerId'            => 'import-manual-manager',
                'managerContext'       => 'import',
                'managerTitle'         => 'Teams',
                'managerDescription'   => '',
                'tournaments'          => $tournaments,
                'pots'                 => $pots,
                'selectedTournamentId' => $selectedTournamentId,
                'selectedPotId'        => $selectedPotId,
                'currentTournamentId'  => $selectedTournamentId,
                'currentPotId'         => $selectedPotId,
                'canManage'            => true,
                'allowUnassigned'      => true,
                'showTournamentFilter' => false,
                'fixedTournamentId'    => $selectedTournamentId,
            ]) ?>
        </div>
    </div>
</div>

<script {csp-script-nonce}>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.js-import-upload-form');
    if (!form) {
        return;
    }

    const modeSelect = form.querySelector('.js-import-mode-select');
    const tournamentSelect = form.querySelector('.js-import-page-tournament-select');
    const potGroups = Array.from(form.querySelectorAll('.js-import-pot-group'));

    const syncMode = () => {
        const needsPot = String(modeSelect?.value || 'global') === 'with_pot';
        potGroups.forEach((group) => {
            group.hidden = !needsPot;
        });
    };

    tournamentSelect?.addEventListener('change', () => {
        const value = String(tournamentSelect.value || '').trim();
        const url = new URL(window.location.href);

        if (value === '') {
            url.searchParams.delete('tournament_id');
            url.searchParams.delete('pot_id');
        } else {
            url.searchParams.set('tournament_id', value);
            url.searchParams.delete('pot_id');
        }

        window.location.href = url.toString();
    });

    modeSelect?.addEventListener('change', syncMode);
    syncMode();
});
</script>
<?= $this->endSection() ?>
