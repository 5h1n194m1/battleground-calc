<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?>Register - Battleground Calc<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid auth-container">
    <div class="auth-card-wrap">
        <section class="card auth-card">
            <div class="card-body auth-card-body">
                <div class="auth-brand-block">
                    <div class="auth-eyebrow">Battleground Calc</div>
                    <h1 class="auth-title">Daftar</h1>
                </div>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <div><?= esc($error) ?></div>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= esc(session('errors')) ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <form action="<?= url_to('register') ?>" method="post" class="auth-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="registerEmail" class="form-label auth-label">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="registerEmail"
                            name="email"
                            inputmode="email"
                            autocomplete="email"
                            placeholder="nama@email.com"
                            value="<?= old('email') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="registerUsername" class="form-label auth-label">Username</label>
                        <input
                            type="text"
                            class="form-control"
                            id="registerUsername"
                            name="username"
                            inputmode="text"
                            autocomplete="username"
                            placeholder="Masukkan username"
                            value="<?= old('username') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="registerPassword" class="form-label auth-label">Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="registerPassword"
                            name="password"
                            autocomplete="new-password"
                            placeholder="Masukkan password"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="registerPasswordConfirm" class="form-label auth-label">Konfirmasi Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="registerPasswordConfirm"
                            name="password_confirm"
                            autocomplete="new-password"
                            placeholder="Ulangi password"
                            required
                        >
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary auth-submit-btn">Daftar</button>
                    </div>
                </form>

                <div class="auth-footer-note">
                    Sudah punya akun?
                    <a href="<?= url_to('login') ?>">Login</a>
                </div>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>