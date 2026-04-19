<form action="<?= esc($action) ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="tournament_id" value="<?= esc((string) $tournament['id']) ?>">

    <div class="mb-3">
        <label class="form-label">Nama Pot</label>
        <input
            type="text"
            class="form-control"
            name="name"
            value="<?= esc(old('name', $pot['name'] ?? '')) ?>"
            required
        >
    </div>

    <div class="mb-3">
        <label class="form-label">Sort Order</label>
        <input
            type="number"
            class="form-control"
            name="sort_order"
            min="0"
            value="<?= esc(old('sort_order', $pot['sort_order'] ?? 0)) ?>"
        >
    </div>

    <button type="submit" class="btn btn-primary"><?= esc($submitLabel) ?></button>
</form>
