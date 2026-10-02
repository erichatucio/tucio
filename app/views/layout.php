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
        :root{color-scheme:light;--ink:#182526;--muted:#617071;--line:#dce5e3;--paper:#fff;--wash:#f4f8f6;--green:#176b55;--green-dark:#10523f;--red:#a33636}
        *{box-sizing:border-box}
        body{margin:0;background:var(--wash);color:var(--ink);font:15px/1.55 Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        a{color:var(--green);text-decoration:none}
        a:hover{text-decoration:underline}
        .topbar{background:#fff;border-bottom:1px solid var(--line)}
        .nav{max-width:1080px;margin:auto;padding:15px 24px;display:flex;align-items:center;justify-content:space-between;gap:18px}
        .brand{font-size:17px;font-weight:750;letter-spacing:-.03em;color:var(--ink)}
        .nav-right{display:flex;align-items:center;gap:18px;color:var(--muted);font-size:14px}
        main{max-width:1080px;margin:44px auto;padding:0 24px 64px}
        .heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}
        h1{margin:0;font-size:30px;line-height:1.2;letter-spacing:-.04em}
        h2{font-size:19px;margin:0 0 8px}
        p{color:var(--muted)}
        .panel{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:26px;box-shadow:0 8px 24px #16382b08}
        .form-panel{max-width:640px}
        .button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--green);border-radius:8px;background:var(--green);color:#fff;padding:10px 15px;font:inherit;font-weight:650;cursor:pointer}
        .button:hover{background:var(--green-dark);text-decoration:none}
        .button.secondary{background:#fff;color:var(--ink);border-color:var(--line)}
        .button.danger{background:var(--red);border-color:var(--red)}
        .button.small{padding:6px 10px;font-size:13px}
        .field{margin:0 0 17px}
        label{display:block;margin-bottom:6px;font-weight:650;font-size:14px}
        input,textarea{width:100%;border:1px solid #cbd7d4;border-radius:8px;padding:11px 12px;color:var(--ink);font:inherit;background:#fff}
        input:focus,textarea:focus{outline:3px solid #176b5520;border-color:var(--green)}
        textarea{min-height:125px;resize:vertical}
        .notice{padding:12px 15px;border-radius:8px;margin:0 0 20px}
        .notice.success{background:#e6f5ed;color:#17583e}
        .notice.error{background:#fff0ee;color:#8d2924}
        .table-wrap{overflow-x:auto}
        table{border-collapse:collapse;width:100%;min-width:720px}
        th,td{text-align:left;padding:13px 12px;border-bottom:1px solid var(--line);vertical-align:top}
        th{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.07em}
        td.actions{white-space:nowrap;text-align:right}
        .empty{text-align:center;padding:45px 15px;color:var(--muted)}
        .actions-row{display:flex;gap:9px;align-items:center;margin-top:22px}
        .identity{white-space:nowrap}
        .nav form{margin:0}
        @media(max-width:620px){.nav{padding:13px 17px}.nav-right{gap:10px}main{margin:28px auto;padding:0 16px 48px}.heading{align-items:flex-start;flex-direction:column}h1{font-size:26px}.panel{padding:19px}}
    </style>
</head>
<body>
<header class="topbar">
    <nav class="nav">
        <a class="brand" href="<?= htmlspecialchars(site_url($username ? 'products' : 'login'), ENT_QUOTES, 'UTF-8') ?>">Stockroom</a>
        <?php if ($username): ?>
            <div class="nav-right">
                <a href="<?= htmlspecialchars(site_url('products'), ENT_QUOTES, 'UTF-8') ?>">Products</a>
                <span class="identity"><?= htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                <form method="post" action="<?= htmlspecialchars(site_url('logout'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= csrf_field() ?>
                    <button class="button secondary small" type="submit">Sign out</button>
                </form>
            </div>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?php if (is_string($success) && $success !== ''): ?>
        <div class="notice success" role="status"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php require $view_path; ?>
</main>
</body>
</html>
