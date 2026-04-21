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
                    <th class="text-center">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pots as $pot): ?>
                    <tr>
                        <td class="fw-semibold">
                            <a href="#pot-<?= esc((string) $pot['id']) ?>" class="text-decoration-none text-dark"><?= esc($pot['name']) ?></a>
                        </td>
                        <td class="text-center"><?= esc((string) $pot['sort_order']) ?></td>
                        <td class="text-center"><?= esc((string) $pot['team_count']) ?></td>
                        <td class="text-center"><?= esc((string) $pot['total_score']) ?></td>
                        <td class="text-center text-muted">
                            <?= (int) $pot['team_count'] > 0 ? 'Scroll ke bawah untuk kelola lengkap' : 'Belum ada team' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
