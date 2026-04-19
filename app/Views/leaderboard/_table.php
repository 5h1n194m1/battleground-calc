<div class="card stat-card">
    <div class="card-body p-0">
        <?php if ($leaderboard === []): ?>
            <div class="p-4 text-center text-muted">Belum ada team atau score pada pot ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Peringkat</th>
                            <th>Team</th>
                            <th class="text-center">Games</th>
                            <th class="text-center">Placement</th>
                            <th class="text-center">Kill</th>
                            <th class="text-center">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc((string) ($index + 1)) ?></td>
                                <td><?= esc($row['name']) ?></td>
                                <td class="text-center"><?= esc((string) $row['games_played']) ?></td>
                                <td class="text-center"><?= esc((string) $row['total_placement']) ?></td>
                                <td class="text-center"><?= esc((string) $row['total_kill']) ?></td>
                                <td class="text-center fw-semibold"><?= esc((string) $row['total_score']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
