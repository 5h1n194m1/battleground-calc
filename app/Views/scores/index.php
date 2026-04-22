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
$isAdmin = auth()->user()?->inGroup('admin') ?? false;
?>

<div class="score-page-shell" id="scorePageHost" data-current-tournament-id="<?= esc((string) $tournament['id']) ?>">
    <div class="page-header page-header-compact score-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1"><?= esc($tournament['name']) ?></h1>
            <div class="page-subtitle">Kelola pot dan score tournament.</div>
        </div>

        <div class="score-page-actions">
            <span class="badge <?= esc($badgeClass) ?> status-chip"><?= esc($statusLabel) ?></span>

            <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-outline-primary btn-sm app-btn controller-only" data-bs-toggle="modal" data-bs-target="#statusTournamentModal">
                    Ubah Status
                </button>
            <?php endif; ?>

            <button
                type="button"
                class="btn btn-outline-secondary btn-sm app-btn js-team-manager-toggle controller-only"
                data-team-manager-toggle="scoreTeamManagerPanel"
                aria-controls="scoreTeamManagerPanel"
                aria-expanded="true"
                <?= $canManage ? '' : 'disabled' ?>
            >
                Team Manager
            </button>

            <button
                type="button"
                class="btn btn-outline-secondary btn-sm app-btn js-controller-visibility-toggle controller-only"
                data-hide-text="Hide Controller"
                data-show-text="Show Controller"
            >
                Hide Controller
            </button>

            <form action="<?= site_url('pots/store') ?>" method="post" class="m-0 controller-only">
                <?= csrf_field() ?>
                <input type="hidden" name="tournament_id" value="<?= esc((string) $tournament['id']) ?>">
                <input type="hidden" name="redirect_to" value="<?= current_url() ?>">
                <button type="submit" class="btn btn-primary btn-sm app-btn" <?= $canManage ? '' : 'disabled' ?>>Add Pot</button>
            </form>

            <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm app-btn controller-only">Dashboard</a>
        </div>
    </div>

    <?php if (! $canManage): ?>
        <div class="score-lock-banner">
            <strong>Mode read only aktif.</strong>
            Tournament ini sudah selesai, jadi semua input score, edit nama, tambah team, tambah pot, hapus pot, dan upload screenshot dikunci.
        </div>
    <?php endif; ?>

    <div class="score-page-workspace is-manager-open" id="scorePageWorkspace">
        <div class="score-page-main">
            <div class="pot-module-stack">
                <?php foreach ($potModules as $module): ?>
                    <?= view('scores/_calculator', [
                        'pot'              => $module['pot'],
                        'teams'            => $module['teams'],
                        'membersByTeam'    => $module['membersByTeam'],
                        'memberTextByTeam' => $module['memberTextByTeam'],
                        'scoresByTeam'     => $module['scoresByTeam'],
                        'totalsByTeam'     => $module['totalsByTeam'],
                        'gameNos'          => $module['gameNos'],
                        'placementMap'     => $placementMap,
                        'canManage'        => $canManage,
                        'isCurrent'        => $module['isCurrent'],
                        'showCardWrapper'  => true,
                        'cardBodyClass'    => 'card-body pot-module-body',
                        'tournamentId'     => $tournament['id'],
                    ]) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <aside id="scoreTeamManagerPanel" class="score-team-manager-panel" data-open="1">
            <?= view('teams/_manager_workspace', [
                'managerId'            => 'score-cms-manager',
                'managerContext'       => 'score',
                'managerTitle'         => 'Team CMS',
                'managerDescription'   => 'Panel satu halaman khusus untuk kelola team dan roster tanpa mengganggu area scoring di kiri.',
                'tournaments'          => $tournaments,
                'pots'                 => $managerPots,
                'selectedTournamentId' => (int) $tournament['id'],
                'selectedPotId'        => (int) $currentPotId,
                'currentTournamentId'  => (int) $tournament['id'],
                'currentPotId'         => (int) $currentPotId,
                'canManage'            => $canManage,
                'allowUnassigned'      => false,
            ]) ?>
        </aside>
    </div>
</div>

<button
    type="button"
    class="btn btn-outline-light btn-sm app-btn controller-restore-btn js-controller-visibility-toggle"
    data-hide-text="Hide Controller"
    data-show-text="Show Controller"
    hidden
>
    Show Controller
</button>

<?php if ($isAdmin): ?>
    <div class="modal fade" id="statusTournamentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <form action="<?= site_url('tournaments/update-status/' . $tournament['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= current_url() ?>">

                    <div class="modal-header">
                        <h2 class="modal-title fs-6">Ubah Status Tournament</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">Status saat ini</label>
                        <select name="status" class="form-select" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm app-btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm app-btn">Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
