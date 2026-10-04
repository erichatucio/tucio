<?php
$username = $session->userdata('username');
$success = $session->flashdata('success');
$view_path = APP_DIR . 'views/' . $content_view . '.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> · Stockroom</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #17212b;
            --muted: #718096;
            --line: #e5eaf0;
            --paper: #fff;
            --wash: #f5f7fb;
            --navy: #172b4d;
            --navy-soft: #263f66;
            --accent: #6c63ff;
            --accent-soft: #f0efff;
            --green: #16876b;
            --red: #c94f5d;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background: var(--wash);
            font: 15px/1.55 Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        button, input, textarea { font: inherit; }
        button:focus-visible, a:focus-visible, input:focus-visible, textarea:focus-visible {
            outline: 3px solid #6c63ff55;
            outline-offset: 2px;
        }
        .app-shell { min-height: 100vh; }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 2;
            display: flex;
            width: 248px;
            flex-direction: column;
            padding: 28px 18px 20px;
            color: #eaf0fb;
            background: var(--navy);
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 10px; }
        .brand-mark {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: 13px;
            color: #fff;
            background: var(--accent);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: -.04em;
        }
        .brand-name { display: block; color: #fff; font-size: 17px; font-weight: 760; letter-spacing: -.04em; }
        .brand-caption { display: block; margin-top: 1px; color: #9bacc8; font-size: 9px; font-weight: 750; letter-spacing: .15em; }
        .sidebar-label { margin: 42px 12px 12px; color: #8fa2c0; font-size: 10px; font-weight: 800; letter-spacing: .16em; }
        .side-link {
            display: flex;
            min-height: 45px;
            align-items: center;
            gap: 12px;
            margin: 2px 0;
            padding: 0 12px;
            border-radius: 10px;
            color: #c4d0e2;
            font-size: 13px;
            font-weight: 650;
            transition: background .16s, color .16s;
        }
        .side-link:hover, .side-link.active { color: #fff; background: #ffffff14; }
        .side-link.active { box-shadow: inset 3px 0 var(--accent); }
        .side-icon { width: 20px; color: #aab9d0; font-size: 17px; text-align: center; }
        .side-link.new-link { margin-top: 12px; color: #fff; background: #6c63ff; }
        .side-link.new-link:hover { background: #5d53ef; }
        .side-link.new-link .side-icon { color: #fff; font-size: 20px; }
        .sidebar-footer {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: auto;
            padding: 15px 12px 2px;
            border-top: 1px solid #ffffff1a;
            color: #b6c4d9;
            font-size: 11px;
        }
        .online-dot { width: 7px; height: 7px; border-radius: 50%; background: #54d6a3; box-shadow: 0 0 0 4px #54d6a322; }
        .workspace { min-height: 100vh; margin-left: 248px; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            min-height: 76px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 12px clamp(22px, 4vw, 54px);
            border-bottom: 1px solid var(--line);
            background: #ffffffed;
            backdrop-filter: blur(12px);
        }
        .breadcrumb { color: #8a96a8; font-size: 12px; font-weight: 600; }
        .breadcrumb span { margin: 0 8px; color: #c3cad4; }
        .breadcrumb strong { color: #35445a; font-weight: 700; }
        .account-area { display: flex; align-items: center; gap: 13px; }
        .account-copy { display: flex; flex-direction: column; line-height: 1.35; }
        .account-copy strong { color: #2c3c52; font-size: 12px; }
        .account-copy small { margin-top: 3px; color: #8b97a8; font-size: 10px; }
        .account-mark { display: grid; width: 36px; height: 36px; place-items: center; border: 1px solid #dedcff; border-radius: 12px; color: #5a51dc; background: var(--accent-soft); font-size: 12px; font-weight: 800; }
        .signout-form { margin: 0 0 0 7px; }
        main { width: min(100%, 1200px); margin: 0 auto; padding: 42px clamp(22px, 4vw, 54px) 64px; }
        .notice { margin: 20px clamp(22px, 4vw, 54px) 0; padding: 13px 16px; border: 1px solid transparent; border-radius: 11px; font-size: 13px; }
        .notice.success { border-color: #c9ebdd; color: #17664f; background: #effaf5; }
        .notice.error { border-color: #f2d5d8; color: #9c3544; background: #fff4f5; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
        .eyebrow { margin: 0 0 7px; color: #756df4; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; color: #1b2940; font-size: clamp(26px, 3vw, 34px); line-height: 1.2; letter-spacing: -.05em; }
        h2 { margin: 0; color: #26364b; font-size: 18px; letter-spacing: -.025em; }
        .page-description { margin: 8px 0 0; color: var(--muted); font-size: 13px; }
        .button {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 15px;
            border: 1px solid var(--accent);
            border-radius: 10px;
            color: #fff;
            background: var(--accent);
            box-shadow: 0 5px 13px #6c63ff26;
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
            transition: transform .16s, background .16s, box-shadow .16s;
        }
        .button:hover { transform: translateY(-1px); background: #5d53ef; box-shadow: 0 7px 16px #6c63ff35; text-decoration: none; }
        .button.secondary { border-color: var(--line); color: #4c5a6d; background: #fff; box-shadow: none; }
        .button.secondary:hover { border-color: #c9c5ff; color: #5148ce; background: #faf9ff; }
        .button.danger { border-color: var(--red); background: var(--red); box-shadow: none; }
        .button.danger:hover { background: #b8404e; }
        .button.small { min-height: 32px; padding: 6px 10px; border-radius: 8px; font-size: 11px; }
        .metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; margin-bottom: 20px; }
        .metric-card { display: flex; min-height: 108px; align-items: center; gap: 14px; padding: 19px; border: 1px solid var(--line); border-radius: 14px; background: var(--paper); box-shadow: 0 4px 14px #233a5907; }
        .metric-icon { display: grid; width: 43px; height: 43px; flex: 0 0 auto; place-items: center; border-radius: 13px; color: #6259e7; background: var(--accent-soft); font-size: 16px; font-weight: 800; }
        .metric-card:nth-child(2) .metric-icon { color: #16876b; background: #e9f8f1; }
        .metric-card:nth-child(3) .metric-icon { color: #c17b27; background: #fff5e8; }
        .metric-label { display: block; color: #8490a1; font-size: 10px; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .metric-value { display: block; margin-top: 2px; color: #26364b; font-size: 21px; font-weight: 780; letter-spacing: -.04em; }
        .panel { overflow: hidden; border: 1px solid var(--line); border-radius: 14px; background: var(--paper); box-shadow: 0 5px 18px #233a5908; }
        .panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 19px 22px; border-bottom: 1px solid #edf0f4; }
        .panel-heading p { margin: 4px 0 0; color: #8792a1; font-size: 11px; }
        .panel-count { padding: 5px 9px; border-radius: 20px; color: #5c54d8; background: var(--accent-soft); font-size: 10px; font-weight: 750; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; min-width: 710px; border-collapse: collapse; }
        th, td { padding: 14px 20px; border-bottom: 1px solid #edf0f4; text-align: left; vertical-align: middle; }
        tr:last-child td { border-bottom: 0; }
        th { color: #8a96a6; background: #fbfcfe; font-size: 9px; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
        td { color: #556276; font-size: 12px; }
        .product-cell { display: flex; min-width: 170px; align-items: center; gap: 11px; }
        .product-mark { display: grid; width: 36px; height: 36px; flex: 0 0 auto; place-items: center; border: 1px solid #e4e2ff; border-radius: 11px; color: #645ce7; background: #f6f5ff; font-size: 11px; font-weight: 800; }
        .product-name { display: block; color: #26364b; font-size: 12px; font-weight: 750; }
        .product-id { display: block; margin-top: 2px; color: #9aa4b1; font-size: 10px; }
        .description-cell { max-width: 250px; color: #8591a1; }
        .price-cell { color: #26364b; font-weight: 750; white-space: nowrap; }
        .stock-badge { display: inline-flex; min-width: 35px; justify-content: center; padding: 4px 8px; border-radius: 20px; color: #167655; background: #eaf8f1; font-size: 10px; font-weight: 750; }
        .stock-badge.empty-stock { color: #ae5660; background: #fff0f1; }
        td.actions { white-space: nowrap; text-align: right; }
        .actions-group { display: flex; justify-content: flex-end; gap: 6px; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); }
        .empty { padding: 54px 18px; color: var(--muted); text-align: center; }
        .empty-mark { display: grid; width: 52px; height: 52px; margin: 0 auto 15px; place-items: center; border-radius: 17px; color: #655ce8; background: var(--accent-soft); font-size: 20px; font-weight: 800; }
        .empty h2 { margin-bottom: 5px; }
        .empty p { margin: 0 0 17px; color: #8290a2; font-size: 12px; }
        .form-panel { max-width: 760px; padding: 25px; }
        .product-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 17px; }
        .field { min-width: 0; margin: 0 0 17px; }
        .field.full-width, .product-form .actions-row { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 7px; color: #3d4b60; font-size: 11px; font-weight: 750; }
        input, textarea { width: 100%; min-width: 0; border: 1px solid #dce3ed; border-radius: 9px; padding: 11px 12px; color: #24344b; background: #fbfcfe; font-size: 12px; transition: border-color .16s, box-shadow .16s, background .16s; }
        input:focus, textarea:focus { border-color: #847cff; outline: none; background: #fff; box-shadow: 0 0 0 3px #6c63ff18; }
        textarea { min-height: 112px; resize: vertical; }
        .field-hint { margin: 6px 0 0; color: #939eac; font-size: 10px; }
        .actions-row { display: flex; align-items: center; gap: 9px; margin-top: 5px; }
        .delete-panel { max-width: 650px; padding: 28px; }
        .warning-mark { display: grid; width: 45px; height: 45px; margin-bottom: 16px; place-items: center; border-radius: 14px; color: #bd5360; background: #fff0f1; font-size: 19px; font-weight: 800; }
        .delete-copy { margin: 8px 0 18px; color: #7b8797; font-size: 12px; }
        .delete-product { margin: 0 0 20px; padding: 14px; border: 1px solid var(--line); border-radius: 10px; color: #435168; background: #fbfcfe; font-size: 12px; }
        .guest { display: grid; min-height: 100vh; place-items: center; padding: 30px 20px; background: radial-gradient(ellipse at 15% 10%, #e5e2ff 0, transparent 34%), radial-gradient(ellipse at 90% 92%, #def5ec 0, transparent 33%), #f5f7fb; }
        .auth-shell { display: grid; width: min(100%, 900px); min-height: 510px; grid-template-columns: 1fr 1fr; overflow: hidden; border: 1px solid #ffffff; border-radius: 23px; background: #fff; box-shadow: 0 28px 90px #233a591a; }
        .auth-brand-panel { display: flex; flex-direction: column; justify-content: space-between; padding: 38px; color: #fff; background: radial-gradient(ellipse at 90% 10%, #475f8c 0, transparent 42%), linear-gradient(145deg, #172b4d, #203a63); }
        .auth-brand-panel .brand { padding: 0; }
        .auth-brand-panel .brand-caption { color: #bcc8dd; }
        .auth-promo { padding: 45px 0; }
        .auth-promo .promo-kicker { margin: 0 0 12px; color: #a9a3ff; font-size: 9px; font-weight: 800; letter-spacing: .18em; }
        .auth-promo h2 { max-width: 330px; color: #fff; font-size: clamp(24px, 4vw, 34px); line-height: 1.15; letter-spacing: -.05em; }
        .auth-promo p { max-width: 325px; margin: 13px 0 0; color: #c1cee1; font-size: 12px; }
        .auth-footnote { margin: 0; color: #a9b8ce; font-size: 10px; }
        .auth-content { display: flex; align-items: center; padding: 42px; }
        .form-panel.login-panel { width: 100%; margin: 0; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
        .login-panel h1 { font-size: 27px; }
        .login-subtitle { margin: 8px 0 23px; color: #8490a1; font-size: 12px; }
        .login-panel .field { margin-bottom: 15px; }
        .login-panel .button { width: 100%; margin-top: 4px; }
        .signout-form .button { min-height: 34px; }
        @media (max-width: 900px) {
            .sidebar { width: 214px; }
            .workspace { margin-left: 214px; }
            .metric-card { padding: 15px; }
            .auth-brand-panel { padding: 30px; }
            .auth-content { padding: 32px; }
        }
        @media (max-width: 680px) {
            .sidebar { position: static; width: auto; padding: 16px 18px 12px; }
            .sidebar-label, .sidebar-footer { display: none; }
            .brand { padding: 0 4px 12px; }
            .sidebar nav { display: flex; align-items: center; gap: 7px; overflow-x: auto; padding-top: 8px; border-top: 1px solid #ffffff1a; }
            .side-link { min-height: 38px; flex: 0 0 auto; margin: 0; padding: 0 10px; font-size: 11px; }
            .side-link.active { box-shadow: inset 0 -2px var(--accent); }
            .side-link.new-link { margin: 0 0 0 auto; }
            .side-icon { width: auto; }
            .workspace { margin-left: 0; }
            .topbar { position: static; min-height: 60px; padding: 10px 18px; }
            .account-copy { display: none; }
            .account-area { gap: 7px; }
            .signout-form { margin-left: 0; }
            .signout-form .button { min-height: 32px; padding: 6px 9px; }
            main { padding: 28px 18px 42px; }
            .notice { margin: 14px 18px 0; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .metrics { grid-template-columns: 1fr; gap: 9px; }
            .metric-card { min-height: 82px; }
            .panel-heading { padding: 16px; }
            .form-panel { padding: 19px; }
        }
        @media (max-width: 560px) {
            .auth-shell { min-height: auto; grid-template-columns: 1fr; }
            .auth-brand-panel { min-height: 180px; padding: 22px; }
            .auth-promo { padding: 20px 0 10px; }
            .auth-promo h2 { max-width: 420px; font-size: 24px; }
            .auth-promo p, .auth-footnote { display: none; }
            .auth-content { padding: 27px 23px; }
            .product-form { grid-template-columns: 1fr; }
            .field.full-width, .product-form .actions-row { grid-column: auto; }
            .actions-row { flex-wrap: wrap; }
            .actions-row .button { flex: 1; }
        }
    </style>
</head>
<body class="<?= $username ? 'authenticated' : 'guest' ?>">
<?php if ($username): ?>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">
                <span class="brand-mark" aria-hidden="true">ST</span>
                <span><span class="brand-name">Stockroom</span><span class="brand-caption">INVENTORY SUITE</span></span>
            </a>
            <p class="sidebar-label">WORKSPACE</p>
            <nav aria-label="Main navigation">
                <a class="side-link active" href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="side-icon" aria-hidden="true">▦</span> Products
                </a>
                <a class="side-link new-link" href="<?= htmlspecialchars(site_url('products/create'), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="side-icon" aria-hidden="true">+</span> New product
                </a>
            </nav>
            <div class="sidebar-footer"><span class="online-dot" aria-hidden="true"></span> Inventory workspace</div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <div class="breadcrumb">Workspace <span>/</span> <strong><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong></div>
                <div class="account-area">
                    <span class="account-mark" aria-hidden="true">ST</span>
                    <span class="account-copy"><strong><?= htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><small>Administrator</small></span>
                    <form class="signout-form" method="post" action="<?= htmlspecialchars(site_url('logout'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= csrf_field() ?>
                        <button class="button secondary small" type="submit">Sign out</button>
                    </form>
                </div>
            </header>
            <?php if (is_string($success) && $success !== ''): ?>
                <div class="notice success" role="status"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <?php endif; ?>
            <main><?php require $view_path; ?></main>
        </div>
    </div>
<?php else: ?>
    <div class="auth-shell">
        <section class="auth-brand-panel" aria-label="Stockroom inventory management">
            <a class="brand" href="<?= htmlspecialchars(site_url('login'), ENT_QUOTES, 'UTF-8') ?>">
                <span class="brand-mark" aria-hidden="true">ST</span>
                <span><span class="brand-name">Stockroom</span><span class="brand-caption">INVENTORY SUITE</span></span>
            </a>
            <div class="auth-promo">
                <p class="promo-kicker">YOUR CATALOG, IN CONTROL</p>
                <h2>Everything in your inventory, all in one place.</h2>
                <p>A calmer way to keep products, pricing, and stock up to date.</p>
            </div>
            <p class="auth-footnote">A clear view of what you have and what comes next.</p>
        </section>
        <main class="auth-content"><?php require $view_path; ?></main>
    </div>
<?php endif; ?>
</body>
</html>
