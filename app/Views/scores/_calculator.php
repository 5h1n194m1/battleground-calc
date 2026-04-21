<?php
$showCardWrapper  = $showCardWrapper ?? true;
$cardBodyClass    = $cardBodyClass ?? 'card-body';
$tableId          = 'scoreCalculatorTable-' . (int) $pot['id'];
$formId           = 'scoreCalculatorForm-' . (int) $pot['id'];
$gameCountId      = 'gameCountInput-' . (int) $pot['id'];
$subHeaderId      = 'gameCountHeaderRow-' . (int) $pot['id'];
$addButtonId      = 'addGameBtn-' . (int) $pot['id'];
$removeButtonId   = 'removeGameBtn-' . (int) $pot['id'];
$screenshotId     = 'potScreenshotsCollapse-' . (int) $pot['id'];
$teamLookupId     = 'teamLookupList-' . (int) $pot['id'];
$playerLookupId   = 'playerLookupList-' . (int) $pot['id'];
$memberTextByTeam = $memberTextByTeam ?? [];
$membersByTeam    = $membersByTeam ?? [];
$tournamentId     = (int) ($tournamentId ?? $pot['tournament_id'] ?? 0);
$canManage        = $canManage ?? true;
$disabledAttr     = $canManage ? '' : 'disabled';
$readonlyAttr     = $canManage ? '' : 'readonly';
$gameCount        = max(1, count($gameNos ?? []));
$allowSideScroll  = $gameCount > 8;
$teamColWidth     = $allowSideScroll ? 136 : max(136, 300 - (($gameCount - 1) * 24));
$scoreColWidth    = $allowSideScroll ? 40 : max(40, 52 - (($gameCount - 1) * 2));
$placementColWidth= $allowSideScroll ? 36 : max(36, 46 - (($gameCount - 1) * 2));
?>

<?php if ($showCardWrapper): ?>
<section class="card pot-module-card <?= ! empty($isCurrent) ? 'pot-module-current' : '' ?>" id="pot-card-<?= esc((string) $pot['id']) ?>" data-pot-card data-pot-id="<?= esc((string) $pot['id']) ?>">
    <div class="<?= esc($cardBodyClass) ?>">
<?php endif; ?>

<div class="pot-module-shell">
    <div class="pot-module-header">
        <div class="pot-module-title-row">
            <div class="pot-order-stack">
                <span class="pot-order-label">POT</span>
                <input
                    type="number"
                    min="1"
                    name="pot_sort_order"
                    form="<?= esc($formId) ?>"
                    class="form-control form-control-sm pot-order-inline js-pot-input"
                    value="<?= esc((string) ($pot['sort_order'] ?? 1)) ?>"
                    <?= $disabledAttr ?>
                >
            </div>

            <input
                type="text"
                name="pot_name"
                form="<?= esc($formId) ?>"
                class="form-control pot-module-title-input js-pot-input"
                value="<?= esc($pot['name']) ?>"
                <?= $readonlyAttr ?>
            >
        </div>

        <div class="pot-toolbar">
            <div class="pot-toolbar-group">
                <form action="<?= site_url('teams/store') ?>" method="post" class="m-0 pot-inline-form js-quick-add-team-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pot_id" value="<?= esc((string) $pot['id']) ?>">
                    <input type="hidden" name="redirect_to" value="<?= current_url() ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm toolbar-btn" <?= $disabledAttr ?>>Add Team</button>
                </form>

                <button type="button" class="btn btn-outline-primary btn-sm toolbar-btn js-add-game" id="<?= esc($addButtonId) ?>" <?= $disabledAttr ?>>+ Game</button>
                <button type="button" class="btn btn-outline-danger btn-sm toolbar-btn js-remove-game" id="<?= esc($removeButtonId) ?>" <?= $disabledAttr ?>>- Game</button>
                <button type="button" class="btn btn-outline-secondary btn-sm toolbar-btn" data-bs-toggle="collapse" data-bs-target="#<?= esc($screenshotId) ?>" aria-expanded="false" aria-controls="<?= esc($screenshotId) ?>">Screenshot</button>

                <form action="<?= site_url('pots/delete/' . $pot['id']) ?>" method="post" class="m-0 js-confirm-delete-form" data-confirm-title="Hapus Pot" data-confirm-message="Yakin ingin menghapus pot ini beserta team dan score di dalamnya?" data-confirm-submit="Hapus Pot">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm toolbar-btn" <?= $disabledAttr ?>>Delete Pot</button>
                </form>

                <div class="score-toolbar-search score-toolbar-search-team">
                    <input type="text" class="form-control form-control-sm js-team-lookup" list="<?= esc($teamLookupId) ?>" placeholder="Cari team...">
                    <datalist id="<?= esc($teamLookupId) ?>" class="js-team-lookup-list"></datalist>
                </div>

                <div class="score-toolbar-search score-toolbar-search-player">
                    <input type="text" class="form-control form-control-sm js-player-lookup" list="<?= esc($playerLookupId) ?>" placeholder="Cari player...">
                    <datalist id="<?= esc($playerLookupId) ?>" class="js-player-lookup-list"></datalist>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse pot-screenshot-collapse" id="<?= esc($screenshotId) ?>">
        <div class="pot-inline-panel">
            <form action="<?= site_url('pots/update-images/' . $pot['id']) ?>" method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Screenshot 1</label>
                    <input type="file" class="form-control form-control-sm" name="reference_image_1" accept=".jpg,.jpeg,.png,.webp,.gif" <?= $disabledAttr ?>>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Screenshot 2</label>
                    <input type="file" class="form-control form-control-sm" name="reference_image_2" accept=".jpg,.jpeg,.png,.webp,.gif" <?= $disabledAttr ?>>
                </div>
                <div class="col-12">
                    <div class="form-text mt-0">Format yang aman: .jpg, .jpeg, .png, .webp, .gif.</div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary toolbar-btn" <?= $disabledAttr ?>>Upload</button>
                </div>
            </form>

            <?php if (! empty($pot['reference_image_1']) || ! empty($pot['reference_image_2'])): ?>
                <div class="score-reference-grid mt-2">
                    <?php if (! empty($pot['reference_image_1'])): ?>
                        <img src="<?= site_url($pot['reference_image_1']) ?>" alt="Reference 1" class="score-reference-image border">
                    <?php endif; ?>
                    <?php if (! empty($pot['reference_image_2'])): ?>
                        <img src="<?= site_url($pot['reference_image_2']) ?>" alt="Reference 2" class="score-reference-image border">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form
        action="<?= site_url('scores/save-bulk') ?>"
        method="post"
        id="<?= esc($formId) ?>"
        class="pot-calculator-form js-score-bulk-form"
        data-can-manage="<?= $canManage ? '1' : '0' ?>"
        data-pot-id="<?= esc((string) $pot['id']) ?>"
        data-pot-update-url="<?= site_url('pots/update/' . $pot['id']) ?>"
        data-placement-map='<?= json_encode($placementMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>'
    >
        <?= csrf_field() ?>
        <input type="hidden" name="pot_id" value="<?= esc((string) $pot['id']) ?>">
        <input type="hidden" name="tournament_id" value="<?= esc((string) $tournamentId) ?>">
        <input type="hidden" name="redirect_to" value="<?= current_url() ?>">
        <input type="hidden" name="game_count" id="<?= esc($gameCountId) ?>" class="js-game-count" value="<?= esc((string) count($gameNos)) ?>">

        <?php if ($teams === []): ?>
            <div class="pot-empty-state">
                <div>Belum ada team di pot ini.</div>
                <div class="small text-muted mt-1">Klik Add Team atau Team Manager untuk menambahkan team baru.</div>
            </div>
        <?php else: ?>
            <div class="score-table-shell<?= $allowSideScroll ? ' is-scrollable' : '' ?>" style="--team-col-width: <?= esc((string) $teamColWidth) ?>px; --score-col-width: <?= esc((string) $scoreColWidth) ?>px; --placement-col-width: <?= esc((string) $placementColWidth) ?>px;">
                <table class="table table-bordered score-table score-calculator-table mb-0" id="<?= esc($tableId) ?>">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-center align-middle score-no-col">No</th>
                            <th rowspan="2" class="align-middle score-team-col">Team</th>
                            <?php foreach ($gameNos as $gameNo): ?>
                                <th colspan="3" class="text-center game-group-head" data-game-group="<?= esc((string) $gameNo) ?>">Game <?= esc((string) $gameNo) ?></th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="text-center align-middle total-col js-total-head">Total</th>
                            <th rowspan="2" class="text-center align-middle action-col">Aksi</th>
                        </tr>
                        <tr id="<?= esc($subHeaderId) ?>">
                            <?php foreach ($gameNos as $gameNo): ?>
                                <th class="text-center game-subhead" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="rank">Rank</th>
                                <th class="text-center game-subhead" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="placement">P.Rank</th>
                                <th class="text-center game-subhead" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="kill">Kill</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($teams as $index => $team): ?>
                            <?php
                            $teamScores   = $scoresByTeam[$team['id']] ?? [];
                            $teamMembers  = array_values(array_filter(array_map(
                                static fn (array $member): string => trim((string) ($member['player_name'] ?? '')),
                                $membersByTeam[$team['id']] ?? []
                            ), static fn (string $name): bool => $name !== ''));
                            $searchLabel  = trim(implode(', ', array_filter([
                                trim((string) $team['name']),
                                implode(', ', $teamMembers),
                            ], static fn (string $value): bool => $value !== '')));
                            $searchPlayerText = implode("\n", $teamMembers);
                            ?>
                            <tr
                                data-team-row
                                data-team-id="<?= esc((string) $team['id']) ?>"
                                data-pot-id="<?= esc((string) $pot['id']) ?>"
                                data-search-label="<?= esc($searchLabel !== '' ? $searchLabel : (string) $team['name']) ?>"
                                data-search-players="<?= esc($searchPlayerText) ?>"
                            >
                                <td class="text-center score-no-col"><?= esc((string) ($index + 1)) ?></td>

                                <td class="score-team-col team-cell">
                                    <div class="team-cell-inner">
                                        <div class="team-main-line">
                                            <input
                                                type="text"
                                                class="form-control form-control-sm fw-semibold team-name-input compact-input js-team-input"
                                                name="team_names[<?= esc((string) $team['id']) ?>]"
                                                value="<?= esc($team['name']) ?>"
                                                <?= $readonlyAttr ?>
                                            >
                                        </div>
                                    </div>
                                </td>

                                <?php foreach ($gameNos as $gameNo): ?>
                                    <?php
                                    $score = $teamScores[$gameNo] ?? null;
                                    $rank  = old("scores.{$team['id']}.{$gameNo}.rank_no", $score['rank_no'] ?? '');
                                    $kill  = old("scores.{$team['id']}.{$gameNo}.kill_point", $score['kill_point'] ?? '');
                                    $placement = $score['placement_point'] ?? 0;
                                    ?>
                                    <td class="game-cell" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="rank">
                                        <input
                                            type="number"
                                            min="1"
                                            max="12"
                                            class="form-control form-control-sm score-input js-score-input"
                                            name="scores[<?= esc((string) $team['id']) ?>][<?= esc((string) $gameNo) ?>][rank_no]"
                                            value="<?= esc((string) $rank) ?>"
                                            <?= $disabledAttr ?>
                                        >
                                    </td>

                                    <td class="score-placement-cell" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="placement">
                                        <?= esc((string) $placement) ?>
                                    </td>

                                    <td class="game-cell" data-game-col="<?= esc((string) $gameNo) ?>" data-col-role="kill">
                                        <input
                                            type="number"
                                            min="0"
                                            class="form-control form-control-sm score-input js-score-input"
                                            name="scores[<?= esc((string) $team['id']) ?>][<?= esc((string) $gameNo) ?>][kill_point]"
                                            value="<?= esc((string) $kill) ?>"
                                            <?= $disabledAttr ?>
                                        >
                                    </td>
                                <?php endforeach; ?>

                                <td class="score-total-cell total-cell"><?= esc((string) ($totalsByTeam[$team['id']] ?? 0)) ?></td>

                                <td class="team-row-actions">
                                    <form action="<?= site_url('teams/delete/' . $team['id']) ?>" method="post" class="m-0 js-team-delete-form" data-team-id="<?= esc((string) $team['id']) ?>" data-pot-id="<?= esc((string) $pot['id']) ?>" data-confirm-title="Hapus Team" data-confirm-message="Yakin ingin menghapus team ini?" data-confirm-submit="Hapus">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm toolbar-btn" <?= $disabledAttr ?>>Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($showCardWrapper): ?>
    </div>
</section>
<?php endif; ?>
