<?php
/** @var array $page */
$page = $page ?? ['title' => '', 'description' => '', 'canonical' => ''];
$siteName = config('site.name');
$phone = config('site.phone');
$phoneNum = config('site.phone_intern');
$title = !empty($page['title']) ? (str_ends_with($page['title'], $siteName) ? $page['title'] : $page['title'] . ' — ' . $siteName) : $siteName;
$desc = !empty($page['description']) ? $page['description'] : config('site.meta');
$canonical = !empty($page['canonical']) ? $page['canonical'] : url($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= url('/assets/img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<a class="skip-link" href="#content">Перейти к содержанию</a>

<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__info">
            <span>Официальные поставки оборудования Huawei в Россию</span>
            <span class="topbar__dot">•</span>
            <a href="<?= url('/contacts') ?>">Офис в Москве</a>
        </div>
        <div class="topbar__contacts">
            <span><?= e(config('site.hours')) ?></span>
            <span class="topbar__dot">•</span>
            <a href="mailto:<?= e(config('site.email')) ?>"><?= e(config('site.email')) ?></a>
        </div>
    </div>
</div>

<header class="header" id="siteHeader">
    <div class="container header__inner">
        <a class="logo" href="<?= url('/') ?>">
            <span class="logo__icon" aria-hidden="true">H</span>
            <span class="logo__text">
                <span class="logo__name">HUAWEI <em>Enterprise</em></span>
                <span class="logo__tagline">Оригинальное оборудование</span>
            </span>
        </a>

        <form class="search" id="searchForm" action="<?= url('/search') ?>" method="get" autocomplete="off">
            <input type="search" name="q" id="searchInput" class="search__input"
                   placeholder="Поиск: Dorado 3000, S5735, 02354CJG…"
                   value="<?= e($_GET['q'] ?? '') ?>" aria-label="Поиск по каталогу">
            <button type="submit" class="search__btn" aria-label="Найти">Найти</button>
            <div class="search-suggest" id="searchSuggest" hidden></div>
        </form>

        <div class="header__actions">
            <a class="header__phone" href="tel:<?= e($phoneNum) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9z"/>
                </svg>
                <span><?= e($phone) ?></span>
            </a>
            <a class="cart-btn" href="<?= url('/cart') ?>" aria-label="Корзина">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="9" cy="21" r="1.5"/><circle cx="19" cy="21" r="1.5"/>
                    <path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 7H6"/>
                </svg>
                <span class="cart-btn__badge" id="cartBadge" hidden>0</span>
            </a>
            <button class="burger" id="burger" aria-label="Открыть меню" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <nav class="nav" aria-label="Основная навигация">
        <div class="container nav__inner">
            <?php foreach (site_menu('top') as $menuItem): ?>
                <a href="<?= e($menuItem['url']) ?>" class="nav__link <?= ($page['active_cat'] ?? '') !== '' && ($page['active_cat'] ?? '') === $menuItem['active_slug'] ? 'nav__link--active' : '' ?>">
                    <?= e($menuItem['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>

<div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <div class="mobile-menu__head">
        <a class="logo" href="<?= url('/') ?>">
            <span class="logo__icon">H</span>
            <span class="logo__text"><span class="logo__name">HUAWEI <em>Enterprise</em></span></span>
        </a>
        <button class="mobile-menu__close" id="mobileMenuClose" aria-label="Закрыть меню">×</button>
    </div>
    <nav class="mobile-menu__nav">
        <?php foreach (site_menu('top') as $menuItem): ?>
            <a href="<?= e($menuItem['url']) ?>" class="mobile-menu__link"><?= e($menuItem['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="mobile-menu__bottom">
        <a class="mobile-menu__phone" href="tel:<?= e($phoneNum) ?>"><?= e($phone) ?></a>
        <a class="mobile-menu__email" href="mailto:<?= e(config('site.email')) ?>"><?= e(config('site.email')) ?></a>
    </div>
</div>
<div class="mobile-menu-backdrop" id="mobileBackdrop" hidden></div>

<main id="content">