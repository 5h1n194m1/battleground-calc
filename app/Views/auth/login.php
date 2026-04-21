<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?>Login - Battleground Calc<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="container-fluid auth-container">
    <div class="auth-card-wrap">
        <section class="card auth-card">
            <div class="card-body auth-card-body">
                <div class="auth-brand-block">
                    <div class="auth-eyebrow">Battleground Calc</div>
                    <h1 class="auth-title">Masuk</h1>
                    <p class="auth-subtitle">Masuk ke panel admin turnamen.</p>
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

                <?php if (session('message') !== null) : ?>
                    <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
                <?php endif ?>

                <form action="<?= url_to('login') ?>" method="post" class="auth-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="loginEmail" class="form-label auth-label">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="loginEmail"
                            name="email"
                            inputmode="email"
                            autocomplete="email"
                            placeholder="nama@email.com"
                            value="<?= old('email') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <label for="loginPassword" class="form-label auth-label mb-0">Password</label>
                            <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                                <a href="<?= url_to('magic-link') ?>" class="auth-inline-link">Magic link</a>
                            <?php endif ?>
                        </div>
                        <input
                            type="password"
                            class="form-control"
                            id="loginPassword"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Masukkan password"
                            required
                        >
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary auth-submit-btn">Login</button>
                    </div>
                </form>

                <?php if (setting('Auth.allowRegistration')) : ?>
                    <div class="auth-footer-note">
                        Belum punya akun?
                        <a href="<?= url_to('register') ?>">Daftar</a>
                    </div>
                <?php endif ?>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>