<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= trim($this->renderSection('title')) !== '' ? trim($this->renderSection('title')) : 'Auth - Battleground Calc' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/dark-app.css?v=' . (@filemtime(FCPATH . 'assets/css/dark-app.css') ?: time())) ?>" rel="stylesheet">
    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="app-dark auth-page">
    <main class="auth-shell">
        <?= $this->renderSection('main') ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <?= $this->renderSection('pageScripts') ?>
</body>
</html>