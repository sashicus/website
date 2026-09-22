<?php
$siteName = config('site.name');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Вход — <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
</head>
<body class="adm-login-body">
<main class="adm-login">
    <form class="adm-login__card" method="post" action="<?= url('/admin/login') ?>">
        <?= csrf_field() ?>
        <a class="adm-login__brand" href="<?= url('/') ?>">HUAWEI <em>Enterprise</em></a>
        <h1 class="adm-login__title">Вход в админку</h1>

        <?php if (!empty($error)): ?>
            <div class="adm-alert adm-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <label class="adm-field">
            <span>Логин</span>
            <input type="text" name="login" class="adm-input" autocomplete="username" required autofocus>
        </label>
        <label class="adm-field">
            <span>Пароль</span>
            <input type="password" name="password" class="adm-input" autocomplete="current-password" required>
        </label>

        <button type="submit" class="adm-btn adm-btn--primary adm-btn--block">Войти</button>
        <a class="adm-login__back" href="<?= url('/') ?>">← Вернуться на сайт</a>
    </form>
</main>
</body>
</html>