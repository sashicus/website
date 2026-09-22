<?php
$adminTitle = $adminTitle ?? 'Управление товарами';
$siteName = config('site.name');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle) ?> — <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
</head>
<body>
<header class="adm-head">
    <div class="adm-wrap adm-head__inner">
        <a class="adm-brand" href="<?= url('/admin') ?>">HUAWEI <em>Enterprise</em><span> админка</span></a>
        <nav class="adm-nav">
            <a href="<?= url('/admin/products') ?>" class="<?= ($adminActive ?? '') === 'products' ? 'is-active' : '' ?>">Товары</a>
            <a href="<?= url('/admin/import') ?>" class="<?= ($adminActive ?? '') === 'import' ? 'is-active' : '' ?>">Импорт</a>
            <a href="<?= url('/admin/categories') ?>" class="<?= ($adminActive ?? '') === 'categories' ? 'is-active' : '' ?>">Категории</a>
            <a href="<?= url('/admin/pages') ?>" class="<?= ($adminActive ?? '') === 'pages' ? 'is-active' : '' ?>">Страницы</a>
            <a href="<?= url('/admin/menu') ?>" class="<?= ($adminActive ?? '') === 'menu' ? 'is-active' : '' ?>">Меню</a>
            <a href="<?= url('/') ?>" target="_blank" rel="noopener">Открыть сайт ↗</a>
            <a href="<?= url('/admin/logout') ?>">Выйти</a>
        </nav>
    </div>
</header>
<main class="adm-main">
    <div class="adm-wrap">