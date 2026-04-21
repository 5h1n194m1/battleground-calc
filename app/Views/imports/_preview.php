<div class="card import-card">
    <div class="card-header import-card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h2 class="h5 mb-0">Preview Import</h2>

        <?php if ($previewRows !== [] && $payload): ?>
            <form action="<?= site_url('imports/registrations') ?>" method="post" class="m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="payload" value="<?= esc($payload) ?>">
                <button type="submit" class="btn btn-success btn-sm app-btn">Simpan ke Database</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
        <?php if ($previewRows === []): ?>
            <div class="import-preview-empty">Belum ada data preview. Upload file CSV untuk melihat hasil parsing.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0 import-table">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Leader</th>
                            <th>Kontak</th>
                            <th>Players</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewRows as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($row['team_name']) ?></td>
                                <td><?= esc($row['leader_name']) ?></td>
                                <td>
                                    <div><?= esc($row['whatsapp']) ?></div>
                                    <div class="text-muted small"><?= esc($row['email']) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['players'] === []): ?>
                                        <span class="text-muted">Tanpa player</span>
                                    <?php else: ?>
                                        <?php foreach ($row['players'] as $player): ?>
                                            <div>
                                                <?= esc($player['player_name']) ?>
                                                <?php if ($player['player_role'] !== ''): ?>
                                                    <span class="text-muted small">(<?= esc($player['player_role']) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>