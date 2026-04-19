<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Import Registrations</h1>
        <p class="text-muted mb-0">Upload file CSV registrasi squad untuk preview lalu simpan ke database.</p>
    </div>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card stat-card">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Upload CSV</h2>
            </div>
            <div class="card-body">
                <form action="<?= site_url('imports/registrations') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview">

                    <div class="mb-3">
                        <label for="registration_file" class="form-label">File Registrasi</label>
                        <input type="file" class="form-control" id="registration_file" name="registration_file" accept=".csv,.txt" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Preview Import</button>
                </form>
            </div>
        </div>

        <div class="card stat-card mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Header CSV yang Didukung</h2>
            </div>
            <div class="card-body">
                <code><?= esc(implode(', ', $expectedHeaders)) ?></code>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <?= view('imports/_preview', ['previewRows' => $previewRows, 'payload' => $payload]) ?>

        <div class="card stat-card mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Registrasi Terbaru</h2>
            </div>
            <div class="card-body p-0">
                <?php if ($recentRegistrations === []): ?>
                    <div class="p-4 text-center text-muted">Belum ada registrasi yang tersimpan.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Team</th>
                                    <th>Leader</th>
                                    <th>WhatsApp</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRegistrations as $registration): ?>
                                    <tr>
                                        <td><?= esc($registration['team_name']) ?></td>
                                        <td><?= esc($registration['leader_name']) ?></td>
                                        <td><?= esc($registration['whatsapp']) ?></td>
                                        <td><?= esc($registration['email']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
