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
$currentStatusValue = (string) ($tournament['status'] ?? 'belum_mulai');
?>

<div class="score-page-shell score-page-shell-centered" id="scorePageHost" data-current-tournament-id="<?= esc((string) $tournament['id']) ?>">
    <div class="page-header page-header-compact score-page-header">
        <div class="page-title-block">
            <form action="<?= site_url('tournaments/update/' . $tournament['id']) ?>" method="post" class="score-title-editor">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= esc($currentStatusValue) ?>">
                <input type="hidden" name="redirect_to" value="<?= esc(current_url()) ?>">
                <input
                    type="text"
                    name="name"
                    class="form-control score-title-input"
                    value="<?= esc($tournament['name']) ?>"
                    <?= $canManage ? '' : 'readonly' ?>
                >
                <?php if ($canManage): ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm app-btn controller-only">Simpan Judul</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="score-page-actions">
            <div class="score-page-actions-left">
                <span class="badge <?= esc($badgeClass) ?> status-chip"><?= esc($statusLabel) ?></span>

                <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm app-btn controller-only" data-bs-toggle="modal" data-bs-target="#statusTournamentModal">
                        Status
                    </button>
                <?php endif; ?>

                <a href="<?= site_url('leaderboard/pot/' . $currentPotId) ?>" class="btn btn-outline-info btn-sm app-btn controller-only">Leaderboard</a>
            </div>

            <div class="score-page-actions-right">
                <div class="score-text-zoom controller-only" aria-label="Kontrol ukuran teks">
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-score-text-zoom" data-zoom-action="decrease">Teks -</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-score-text-zoom" data-zoom-action="reset">Normal</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-score-text-zoom" data-zoom-action="increase">Teks +</button>
                </div>

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm app-btn js-team-manager-toggle controller-only"
                    data-team-manager-toggle="scoreTeamManagerPanel"
                    aria-controls="scoreTeamManagerPanel"
                    aria-expanded="false"
                    <?= $canManage ? '' : 'disabled' ?>
                >
                    Team Manager
                </button>

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm app-btn js-hide-controller controller-only"
                >
                    Hide Controller
                </button>

                <button
                    type="button"
                    class="btn btn-primary btn-sm app-btn js-show-controller"
                    hidden
                >
                    Show Controller
                </button>
            </div>
        </div>
    </div>

    <?php if (! $canManage): ?>
        <div class="score-lock-banner">
            <strong>Read only.</strong>
            Tournament selesai.
        </div>
    <?php endif; ?>

    <div class="score-page-workspace" id="scorePageWorkspace">
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
                        'tournamentName'   => $tournament['name'],
                    ]) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="score-side-panel">
            <aside id="scoreTeamManagerPanel" class="score-team-manager-panel" data-open="0" hidden>
                <?= view('teams/_manager_workspace', [
                    'managerId'            => 'score-cms-manager',
                    'managerContext'       => 'score',
                    'managerTitle'         => 'Team',
                    'managerDescription'   => '',
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
</div>



<?php if ($isAdmin): ?>
    <div class="modal fade" id="statusTournamentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <form action="<?= site_url('tournaments/update-status/' . $tournament['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= current_url() ?>">

                    <div class="modal-header">
                        <h2 class="modal-title fs-6">Status</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">Status</label>
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
