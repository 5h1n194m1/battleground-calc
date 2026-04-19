<?php
$rankValue       = old('rank_no', $score['rank_no'] ?? '');
$killValue       = old('kill_point', $score['kill_point'] ?? 0);
$placementValue  = $score['placement_point'] ?? '-';
$matchTotalValue = $score['total_point'] ?? '-';
?>
<tr>
    <td><?= esc((string) ($index + 1)) ?></td>
    <td class="fw-semibold"><?= esc($team['name']) ?></td>
    <td colspan="6">
        <form action="<?= site_url('scores/save') ?>" method="post" class="row g-2 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="pot_id" value="<?= esc((string) $pot['id']) ?>">
            <input type="hidden" name="team_id" value="<?= esc((string) $team['id']) ?>">
            <input type="hidden" name="game_no" value="<?= esc((string) $selectedGameNo) ?>">

            <div class="col-md-2">
                <input type="number" name="rank_no" min="1" class="form-control text-center" value="<?= esc((string) $rankValue) ?>" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="kill_point" min="0" class="form-control text-center" value="<?= esc((string) $killValue) ?>" required>
            </div>
            <div class="col-md-2 text-center">
                <span class="badge text-bg-secondary"><?= esc((string) $placementValue) ?></span>
            </div>
            <div class="col-md-2 text-center">
                <span class="badge text-bg-primary"><?= esc((string) $matchTotalValue) ?></span>
            </div>
            <div class="col-md-2 text-center fw-semibold">
                <?= esc((string) $totalScore) ?>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" class="btn btn-sm btn-success w-100">Simpan</button>
            </div>
        </form>
    </td>
</tr>
