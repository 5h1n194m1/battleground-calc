<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1"><?= esc($formTitle) ?></h1>
        <p class="text-muted mb-0">Isi nama dan status tournament lalu simpan perubahan.</p>
    </div>
    <a href="<?= site_url('tournaments') ?>" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="<?= esc($action) ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="name" class="form-label">Nama Tournament</label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    value="<?= esc(old('name', $tournament['name'] ?? '')) ?>"
                    required
                >
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status Tournament</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= old('status', $tournament['status'] ?? 'belum_mulai') === $value ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= esc($submitLabel) ?></button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
