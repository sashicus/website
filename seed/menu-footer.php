<?php

declare(strict_types=1);

/**
 * Seed: заполняет футер-меню по умолчанию (footer-catalog + footer-info),
 * если соответствующие позиции ещё пусты. Верхнее меню не трогает.
 * Run:  php seed/menu-footer.php
 * Безопасно повторять.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

function countFor(string $position): int
{
    return (int) db()->value('SELECT COUNT(*) FROM menu_items WHERE position = ?', [$position]);
}

$catalogCount = countFor('footer-catalog');
if ($catalogCount === 0) {
    $sort = 0;
    $roots = db()->run('SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY sort_order, id');
    foreach ($roots as $cat) {
        db()->exec(
            'INSERT INTO menu_items (category_id, label, url, position, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)',
            [(int) $cat['id'], '', '', 'footer-catalog', ++$sort]
        );
        echo '  + /' . $cat['name'] . "\n";
    }
    echo "footer-catalog: добавлено $sort пунктов.\n";
} else {
    echo "footer-catalog уже заполнен ($catalogCount пунктов), пропускаем.\n";
}

$info = [
    ['Поиск товаров', '/search'],
    ['О компании', '/about'],
    ['Контакты', '/contacts'],
    ['Корзина', '/cart'],
    ['Карта сайта', '/sitemap.xml'],
];
$infoCount = countFor('footer-info');
if ($infoCount === 0) {
    foreach ($info as $i => [$label, $url]) {
        db()->exec(
            'INSERT INTO menu_items (category_id, label, url, position, sort_order, is_active) VALUES (NULL, ?, ?, ?, ?, 1)',
            [$label, $url, 'footer-info', $i + 1]
        );
        echo '  + ' . $label . ' → ' . $url . "\n";
    }
    echo 'footer-info: добавлено ' . count($info) . " пунктов.\n";
} else {
    echo "footer-info уже заполнен ($infoCount пунктов), пропускаем.\n";
}