<section class="panel form-panel" style="margin:7vh auto 0">
    <p style="margin:0 0 8px;color:#176b55;font-weight:700">PRODUCT MANAGEMENT</p>
    <h1>Welcome back</h1>
    <p style="margin:8px 0 24px">Sign in to manage your product inventory.</p>
    <?php if (!empty($error)): ?>
        <div class="notice error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars(site_url('login'), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" autocomplete="username" required maxlength="100">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button" type="submit">Sign in</button>
    </form>
</section>
