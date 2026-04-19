<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div>
        <h1 class="h3 mb-1">Tournament</h1>
        <p class="text-muted mb-0">Kelola daftar turnamen battleground dan turunannya.</p>
    </div>
    <a href="<?= site_url('tournaments/create') ?>" class="btn btn-primary">Tambah Tournament</a>
</div>

<?= $this->include('tournaments/_table') ?>
<?= $this->endSection() ?>
