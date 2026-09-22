<?php

declare(strict_types=1);

/**
 * Seed: создаёт страницы по умолчанию («О компании», «Контакты») с SEO-полями.
 * Run:  php seed/pages.php
 * Безопасно повторять — пропускаются уже существующие слаги.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

$siteName = config('site.name');
$phone = config('site.phone');
$phoneIntern = config('site.phone_intern');
$email = config('site.email');
$address = config('site.address');
$hours = config('site.hours');

$aboutContent = sprintf(
    <<<'HTML'
<h2>О компании</h2>
<p>%1$s — поставщик серверного оборудования и корпоративных решений HUAWEI для бизнеса.
Работаем с интеграторами, дата-центрами и предприятиями по всей России. Подбираем конфигурации под задачи,
согласовываем сроки поставки и сопровождаем проект от запроса до ввода в эксплуатацию.</p>

<h2>Что мы делаем</h2>
<ul>
<li>Подбор серверов, СХД и сетевого оборудования HUAWEI под задачу;</li>
<li>Расчёт конфигураций под нагрузку и бюджет;</li>
<li>Доставка по России и сопровождение сделки;</li>
<li>Консультации по лицензированию и гарантии.</li>
</ul>

<h2>Почему HUAWEI Enterprise</h2>
<p>Оборудование HUAWEI — это высокая плотность вычислений, энергоэффективность и современная
платформа управления. Мы помогаем собрать действительно подходящую конфигурацию, а не «коробочное»
решение из прайса.</p>

<p>По любым вопросам — <a href="%2$s">напишите нам</a> или закажите <a href="%3$s">обратный звонок</a>.</p>
HTML,
    e($siteName),
    '/contacts',
    '/contacts'
);

$contactsContent = sprintf(
    <<<'HTML'
<div class="contacts">
    <div class="contacts__card">
        <h2>Телефон</h2>
        <p><a class="contacts__phone" href="tel:%1$s">%2$s</a></p>
    </div>
    <div class="contacts__card">
        <h2>E-mail</h2>
        <p><a class="contacts__email" href="mailto:%3$s">%3$s</a></p>
    </div>
    <div class="contacts__card">
        <h2>Адрес</h2>
        <p><address class="contacts__addr">%4$s</address></p>
    </div>
    <div class="contacts__card">
        <h2>Режим работы</h2>
        <p class="contacts__hours">%5$s</p>
    </div>
</div>
HTML,
    e($phoneIntern),
    e($phone),
    e($email),
    e($address),
    e($hours)
);

$pages = [
    [
        'slug'             => 'about',
        'title'            => 'О компании',
        'content'          => $aboutContent,
        'meta_title'       => 'О компании — ' . $siteName,
        'meta_description' => 'Поставщик серверного оборудования HUAWEI Enterprise: подбор конфигураций, доставка по России, сопровождение проектов.',
        'sort_order'       => 10,
        'is_active'        => 1,
    ],
    [
        'slug'             => 'contacts',
        'title'            => 'Контакты',
        'content'          => $contactsContent,
        'meta_title'       => 'Контакты — ' . $siteName,
        'meta_description' => 'Телефон, e-mail и адрес ' . $siteName . '. Консультации по серверному оборудованию HUAWEI Enterprise.',
        'sort_order'       => 20,
        'is_active'        => 1,
    ],
];

$pagesModel = new \App\Models\Page();

$created = 0;
foreach ($pages as $page) {
    $slug = $page['slug'];
    if ($pagesModel->bySlug($slug) !== null) {
        echo "  = /{$slug} уже существует, пропускаем.\n";
        continue;
    }
    $pagesModel->create($page);
    echo "  + /{$slug} создана.\n";
    $created++;
}

echo $created > 0 ? "Готово: создано {$created} страниц.\n" : "Ничего не добавлял.\n";