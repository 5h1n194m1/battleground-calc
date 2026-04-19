<div class="card stat-card mb-4">
    <div class="card-body">
        <form action="<?= site_url('pots/' . $pot['id'] . '/scores') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label for="game_no" class="form-label">Game No</label>
                <select name="game_no" id="game_no" class="form-select">
                    <?php foreach ($gameNos as $gameNo): ?>
                        <option value="<?= esc((string) $gameNo) ?>" <?= $selectedGameNo === $gameNo ? 'selected' : '' ?>>
                            Game <?= esc((string) $gameNo) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="<?= esc((string) ($selectedGameNo + 1)) ?>">Game <?= esc((string) ($selectedGameNo + 1)) ?> (baru)</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </div>
        </form>
    </div>
</div>
