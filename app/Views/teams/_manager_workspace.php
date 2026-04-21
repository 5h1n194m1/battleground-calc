<?php
$managerId = $managerId ?? 'default';
$managerContext = $managerContext ?? 'import';
$managerTitle = $managerTitle ?? 'Team Manager';
$managerDescription = $managerDescription ?? 'Kelola team dan anggota secara manual.';
$selectedTournamentId = (int) ($selectedTournamentId ?? 0);
$selectedPotId = (int) ($selectedPotId ?? 0);
$tournaments = $tournaments ?? [];
$pots = $pots ?? [];
$currentTournamentId = (int) ($currentTournamentId ?? 0);
$currentPotId = (int) ($currentPotId ?? 0);
$canManage = $canManage ?? true;
?>
<div
    class="team-manager-workspace js-team-manager-workspace"
    data-manager-id="<?= esc($managerId) ?>"
    data-context="<?= esc($managerContext) ?>"
    data-endpoint="<?= site_url('teams/manager-data') ?>"
    data-create-url="<?= site_url('teams/store') ?>"
    data-update-url-base="<?= site_url('teams/update') ?>"
    data-delete-url-base="<?= site_url('teams/delete') ?>"
    data-selected-tournament-id="<?= esc((string) $selectedTournamentId) ?>"
    data-selected-pot-id="<?= esc((string) $selectedPotId) ?>"
    data-current-tournament-id="<?= esc((string) $currentTournamentId) ?>"
    data-current-pot-id="<?= esc((string) $currentPotId) ?>"
    data-can-manage="<?= $canManage ? '1' : '0' ?>"
>
    <div class="team-manager-card">
        <div class="team-manager-head">
            <div>
                <h2 class="team-manager-title"><?= esc($managerTitle) ?></h2>
                <div class="team-manager-subtitle"><?= esc($managerDescription) ?></div>
            </div>
            <?php if ($managerContext === 'score'): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-team-manager-close" data-team-manager-close>Hide</button>
            <?php endif; ?>
        </div>

        <div class="team-manager-filters">
            <div class="team-manager-filter">
                <label class="form-label">Tournament</label>
                <select class="form-select form-select-sm js-manager-tournament" <?= $canManage ? '' : 'disabled' ?>>
                    <?php foreach ($tournaments as $tournament): ?>
                        <option value="<?= esc((string) $tournament['id']) ?>" <?= (int) $tournament['id'] === $selectedTournamentId ? 'selected' : '' ?>>
                            <?= esc($tournament['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="team-manager-filter">
                <label class="form-label">Pot</label>
                <select class="form-select form-select-sm js-manager-pot" <?= $canManage ? '' : 'disabled' ?>>
                    <?php foreach ($pots as $pot): ?>
                        <option value="<?= esc((string) $pot['id']) ?>" <?= (int) $pot['id'] === $selectedPotId ? 'selected' : '' ?>>
                            <?= esc($pot['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="team-manager-body">
            <div class="team-manager-form-shell">
                <div class="team-manager-form-head">
                    <strong>Editor Team</strong>
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-manager-reset">Reset</button>
                </div>

                <form class="team-manager-form js-manager-form" novalidate>
                    <input type="hidden" class="js-manager-team-id" value="">

                    <div class="mb-2">
                        <label class="form-label">Nama Team</label>
                        <input type="text" class="form-control form-control-sm js-manager-team-name" placeholder="Masukkan nama team" <?= $canManage ? '' : 'disabled' ?>>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Anggota Team</label>
                        <textarea class="form-control form-control-sm team-manager-member-input js-manager-member-text" rows="4" placeholder="Nick1, Nick2, Nick3, Nick4, Nick5, Nick6" <?= $canManage ? '' : 'disabled' ?>></textarea>
                        <div class="team-manager-help">Pisahkan anggota dengan koma. Maksimal 6 member.</div>
                    </div>

                    <div class="team-manager-actions">
                        <button type="button" class="btn btn-primary btn-sm app-btn js-manager-create" <?= $canManage ? '' : 'disabled' ?>>Tambah Team</button>
                        <button type="button" class="btn btn-outline-primary btn-sm app-btn js-manager-update" <?= $canManage ? '' : 'disabled' ?>>Update Team</button>
                        <button type="button" class="btn btn-outline-danger btn-sm app-btn js-manager-delete" <?= $canManage ? '' : 'disabled' ?>>Hapus Team</button>
                    </div>
                </form>
            </div>

            <div class="team-manager-list-shell">
                <div class="team-manager-list-head">
                    <strong>Daftar Team</strong>
                    <span class="team-manager-count js-manager-count">0 team</span>
                </div>
                <div class="team-manager-list js-manager-list">
                    <div class="team-manager-empty">Belum ada team di pot ini.</div>
                </div>
            </div>
        </div>
    </div>
</div>
