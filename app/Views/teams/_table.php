<div class="card stat-card">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Daftar Team</h2>
    </div>
    <div class="card-body p-0">
        <?php if ($teams === []): ?>
            <div class="p-4 text-center text-muted">Belum ada team pada pot ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th class="text-center">Urutan</th>
                            <th class="text-center">Games</th>
                            <th class="text-center">Total Score</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($team['name']) ?></td>
                                <td class="text-center"><?= esc((string) $team['sort_order']) ?></td>
                                <td class="text-center"><?= esc((string) $team['games_played']) ?></td>
                                <td class="text-center"><?= esc((string) $team['total_score']) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTeamModal<?= $team['id'] ?>">Edit</button>
                                        <form action="<?= site_url('teams/delete/' . $team['id']) ?>" method="post" onsubmit="return confirm('Hapus team ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editTeamModal<?= $team['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2 class="modal-title fs-5">Edit Team</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?= view('teams/form', [
                                                'action'      => site_url('teams/update/' . $team['id']),
                                                'team'        => $team,
                                                'pot'         => $pot,
                                                'submitLabel' => 'Update Team',
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
