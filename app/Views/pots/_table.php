<div class="card stat-card">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Daftar Pot</h2>
    </div>
    <div class="card-body p-0">
        <?php if ($pots === []): ?>
            <div class="p-4 text-center text-muted">Belum ada pot untuk tournament ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th class="text-center">Urutan</th>
                            <th class="text-center">Team</th>
                            <th class="text-center">Total Score</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pots as $pot): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($pot['name']) ?></td>
                                <td class="text-center"><?= esc((string) $pot['sort_order']) ?></td>
                                <td class="text-center"><?= esc((string) $pot['team_count']) ?></td>
                                <td class="text-center"><?= esc((string) $pot['total_score']) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                        <a href="<?= site_url('pots/' . $pot['id'] . '/teams') ?>" class="btn btn-sm btn-outline-primary">Team</a>
                                        <a href="<?= site_url('pots/' . $pot['id'] . '/scores') ?>" class="btn btn-sm btn-outline-success">Score</a>
                                        <a href="<?= site_url('leaderboard/pot/' . $pot['id']) ?>" class="btn btn-sm btn-outline-dark">Leaderboard</a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPotModal<?= $pot['id'] ?>">Edit</button>
                                        <form action="<?= site_url('pots/delete/' . $pot['id']) ?>" method="post" onsubmit="return confirm('Hapus pot ini beserta seluruh team dan score?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editPotModal<?= $pot['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2 class="modal-title fs-5">Edit Pot</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?= view('pots/form', [
                                                'action'      => site_url('pots/update/' . $pot['id']),
                                                'pot'         => $pot,
                                                'tournament'  => $tournament,
                                                'submitLabel' => 'Update Pot',
                                            ]) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
