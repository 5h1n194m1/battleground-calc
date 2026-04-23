<?php
$teams = $pot['management']['teams'] ?? [];
$membersByTeam = $pot['management']['membersByTeam'] ?? [];
$scoresByTeam = $pot['management']['scoresByTeam'] ?? [];
$totalsByTeam = $pot['management']['totalsByTeam'] ?? [];
$gameNos = $pot['management']['gameNos'] ?? [1];
?>

<section class="card stat-card pot-manager-card mb-4" id="pot-<?= esc((string) $pot['id']) ?>">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h4 mb-1"><?= esc($pot['name']) ?></h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="small text-muted align-self-center"><?= esc((string) count($teams)) ?> team</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editPotModal<?= $pot['id'] ?>">Edit Pot</button>
            <form action="<?= site_url('pots/delete/' . $pot['id']) ?>" method="post" onsubmit="return confirm('Hapus pot ini beserta seluruh team dan score?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Pot</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-xl-3">
                <div class="card border-0 bg-light-subtle h-100">
                    <div class="card-body">
                        <h3 class="h6 mb-3">Team</h3>
                        <?= view('teams/form', [
                            'action'      => site_url('teams/store'),
                            'team'        => null,
                            'pot'         => $pot,
                            'submitLabel' => 'Tambah Team',
                        ]) ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Team</th>
                                <th>Anggota</th>
                                <th class="text-center">Games</th>
                                <th class="text-center">Total</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($teams === []): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada team.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teams as $team): ?>
                                    <?php $members = $membersByTeam[$team['id']] ?? []; ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <div><?= esc($team['name']) ?></div>
                                            <div class="small text-muted">Urutan <?= esc((string) $team['sort_order']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($members === []): ?>
                                                <span class="text-muted">-</span>
                                            <?php else: ?>
                                                <?php foreach ($members as $member): ?>
                                                    <div>
                                                        <?= esc($member['player_name']) ?>
                                                        <?php if (! empty($member['player_role'])): ?>
                                                            <span class="text-muted small">(<?= esc($member['player_role']) ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= esc((string) $team['games_played']) ?></td>
                                        <td class="text-center fw-semibold"><?= esc((string) $team['total_score']) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="col-12">
                <?= view('scores/_calculator', [
                    'pot'            => $pot,
                    'teams'          => $teams,
                    'scoresByTeam'   => $scoresByTeam,
                    'totalsByTeam'   => $totalsByTeam,
                    'gameNos'        => $gameNos,
                    'placementMap'   => $placementMap,
                    'showCardWrapper'=> true,
                    'showTitle'      => true,
                    'showHint'       => false,
                ]) ?>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
            <a href="<?= site_url('leaderboard/pot/' . $pot['id']) ?>" class="btn btn-outline-dark btn-sm">Hasil</a>
            <a href="<?= site_url('pots/' . $pot['id'] . '/scores') ?>" class="btn btn-outline-success btn-sm">Buka</a>
        </div>
    </div>
</section>

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
