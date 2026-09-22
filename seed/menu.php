<?php

declare(strict_types=1);

/**
 * Seed: заполняет menu_items пунктами по умолчанию (верхние категории + статичные страницы).
 * Run:  php seed/menu.php
 * Безопасно повторять — заполняется только пустая таблица.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

$count = (int) db()->value('SELECT COUNT(*) FROM menu_items');
if ($count > 0) {
    echo "Меню уже заполнено ($count пунктов), пропускаем.\n";
    exit(0);
}

$sort = 0;
$roots = db()->run('SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY sort_order, id');
foreach ($roots as $cat) {
    db()->exec(
        'INSERT INTO menu_items (category_id, label, url, position, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)',
        [(int) $cat['id'], '', '', 'top', ++$sort]
    );
    echo '  + /' . $cat['name'] . "\n";
}

$static = [
    ['О компании', '/about'],
    ['Контакты', '/contacts'],
];
foreach ($static as [$label, $url]) {
    db()->exec(
        'INSERT INTO menu_items (category_id, label, url, position, sort_order, is_active) VALUES (NULL, ?, ?, ?, ?, 1)',
        [$label, $url, 'top', ++$sort]
    );
    echo '  + ' . $label . ' → ' . $url . "\n";
}

echo "Готово: добавлено $sort пунктов меню.\n";