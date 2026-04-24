<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-page roster-page-shell">
    <div class="page-header dashboard-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1">Export Template CSV</h1>
            <p class="page-subtitle mb-0">Format export disusun supaya gampang dibuka di Excel atau di-copy ke template live score yang formatnya sama.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('teams/roster?tournament_id=' . (int) $tournament['id']) ?>" class="btn btn-outline-secondary btn-sm app-btn">Kembali ke Roster</a>
        </div>
    </div>

    <div class="dashboard-card card mb-3">
        <div class="dashboard-card-header card-header">
            <h2><?= esc((string) ($tournament['name'] ?? 'Tournament')) ?></h2>
        </div>
        <div class="dashboard-card-body card-body">
            <p class="mb-0">Setiap pot punya file CSV sendiri. Kalau lebih nyaman, Anda juga bisa copy blok tab-delimited lalu paste langsung ke Excel/template.</p>
        </div>
    </div>

    <?php if (($unassignedTeams ?? []) !== []): ?>
        <div class="alert alert-warning">
            <strong>Masih ada tim tanpa pot:</strong>
            <?= esc(implode(', ', array_map(static fn (array $team): string => (string) ($team['name'] ?? '-'), $unassignedTeams))) ?>
        </div>
    <?php endif; ?>

    <?php if (($exports ?? []) === []): ?>
        <div class="dashboard-card card">
            <div class="dashboard-card-body card-body">
                Belum ada pot di tournament ini.
            </div>
        </div>
    <?php else: ?>
        <div class="d-grid gap-3">
            <?php foreach ($exports as $export): ?>
                <div class="dashboard-card card">
                    <div class="dashboard-card-header card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <h2 class="mb-1"><?= esc((string) ($export['pot']['name'] ?? 'Pot')) ?></h2>
                            <div class="form-text m-0"><?= esc((string) count($export['teams'] ?? [])) ?> team, <?= esc((string) ($export['gameCount'] ?? 0)) ?> game</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="<?= site_url('teams/export-template/csv/' . (int) $export['pot']['id']) ?>" class="btn btn-primary btn-sm app-btn">Download CSV</a>
                            <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-copy-export" data-copy-target="export-copy-<?= (int) $export['pot']['id'] ?>">Copy Buat Paste</button>
                        </div>
                    </div>
                    <div class="dashboard-card-body card-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <tbody>
                                    <?php foreach (($export['matrix'] ?? []) as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $cell): ?>
                                                <td><?= esc((string) $cell) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <textarea id="export-copy-<?= (int) $export['pot']['id'] ?>" class="form-control" rows="8" readonly><?= esc((string) ($export['clipboardText'] ?? '')) ?></textarea>
                        <div class="form-text">Blok ini pakai tab separator, jadi lebih aman untuk paste langsung ke Excel.</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script {csp-script-nonce}>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-copy-export').forEach((button) => {
        button.addEventListener('click', async () => {
            const targetId = String(button.dataset.copyTarget || '').trim();
            const target = targetId !== '' ? document.getElementById(targetId) : null;
            if (!target) {
                return;
            }

            target.focus();
            target.select();

            try {
                await navigator.clipboard.writeText(target.value);
                button.textContent = 'Sudah Dicopy';
                window.setTimeout(() => {
                    button.textContent = 'Copy Buat Paste';
                }, 1800);
            } catch (error) {
                document.execCommand('copy');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
