<div class="card stat-card">
    <div class="card-body p-0">
        <?php if ($tournaments === []): ?>
            <div class="p-4 text-center text-muted">Belum ada tournament. Tambahkan tournament pertama untuk mulai.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th class="text-center">Pot</th>
                            <th class="text-center">Team</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tournaments as $tournament): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($tournament['name']) ?></td>
                                <td class="text-center"><?= esc((string) $tournament['pot_count']) ?></td>
                                <td class="text-center"><?= esc((string) $tournament['team_count']) ?></td>
                                <td><?= esc(date('d M Y H:i', strtotime((string) $tournament['created_at']))) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="<?= site_url('tournaments/' . $tournament['id'] . '/pots') ?>" class="btn btn-sm btn-outline-primary">Pot</a>
                                        <a href="<?= site_url('tournaments/edit/' . $tournament['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form action="<?= site_url('tournaments/delete/' . $tournament['id']) ?>" method="post" onsubmit="return confirm('Hapus tournament ini beserta seluruh data turunannya?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
