<div class="card stat-card">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Input per Team untuk Game <?= esc((string) $selectedGameNo) ?></h2>
    </div>
    <div class="card-body p-0">
        <?php if ($teams === []): ?>
            <div class="p-4 text-center text-muted">Belum ada team pada pot ini. Tambahkan team terlebih dahulu.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Team</th>
                            <th class="text-center">Rank</th>
                            <th class="text-center">Kill</th>
                            <th class="text-center">Placement</th>
                            <th class="text-center">Total Match</th>
                            <th class="text-center">Akumulasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $index => $team): ?>
                            <?= view('scores/_row', [
                                'index'          => $index,
                                'team'           => $team,
                                'score'          => $scoresByTeam[$team['id']] ?? null,
                                'selectedGameNo' => $selectedGameNo,
                                'totalScore'     => $totalsByTeam[$team['id']] ?? 0,
                                'pot'            => $pot,
                            ]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
