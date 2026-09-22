<?php

declare(strict_types=1);

/**
 * Seed importer: reads data/products.json and fills MySQL.
 * Run:  php seed/seed.php   (env vars SHOP_DB_* to override connection)
 * Safe to re-run — truncates products and re-inserts.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

$jsonFile = dirname(__DIR__) . '/data/products.json';
if (!is_file($jsonFile)) {
    fwrite(STDERR, "products.json not found: $jsonFile\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($jsonFile), true);
$productsRaw = $data['products'] ?? [];
echo 'Загружено товаров из JSON: ' . count($productsRaw) . "\n";

// ---------------- Categories ----------------
$categoriesDef = [
    ['slug' => 'storage',     'name' => 'Системы хранения данных',   'parent' => null, 'sort' => 1],
    ['slug' => 'disks',       'name' => 'Накопители и дисковые полки', 'parent' => null, 'sort' => 2],
    ['slug' => 'disks-ssd',   'name' => 'Диски SSD NVMe',            'parent' => 'disks', 'sort' => 1],
    ['slug' => 'disks-sas',   'name' => 'Диски NL-SAS',              'parent' => 'disks', 'sort' => 2],
    ['slug' => 'enclosures',  'name' => 'Дисковые полки',            'parent' => 'disks', 'sort' => 3],
    ['slug' => 'networking',  'name' => 'Сетевое оборудование',       'parent' => null, 'sort' => 3],
    ['slug' => 'switches',    'name' => 'Коммутаторы',               'parent' => 'networking', 'sort' => 1],
    ['slug' => 'routers',     'name' => 'Маршрутизаторы и модули',    'parent' => 'networking', 'sort' => 2],
    ['slug' => 'io',          'name' => 'Интерфейсные карты SmartIO', 'parent' => null, 'sort' => 4],
    ['slug' => 'optics',      'name' => 'Трансиверы и оптические модули', 'parent' => null, 'sort' => 5],
];

$desc = [
    'storage' => 'Высокопроизводительные системы хранения данных Huawei OceanStor Dorado и OceanStor на базе NVMe-архитектуры и гибридных решений.',
    'disks' => 'Оригинальные накопители Huawei: NVMe SSD, диски NL-SAS и дисковые полки расширения для систем хранения OceanStor.',
    'networking' => 'Коммутаторы, маршрутизаторы и модули расширения Huawei для построения корпоративных сетей.',
    'io' => 'Универсальные I/O модули SmartIO: Fibre Channel 32Gb, Ethernet 25/100Gb и SAS 12G.',
    'optics' => 'Оптические трансиверы Huawei SFP, eSFP, SFP+ для серверов, коммутаторов и систем хранения.',
];

// Clear products (keep categories, refresh rows)
db()->exec('SET FOREIGN_KEY_CHECKS = 0');
db()->exec('TRUNCATE TABLE products');
db()->exec('SET FOREIGN_KEY_CHECKS = 1');

foreach ($categoriesDef as $def) {
    $parentId = null;
    if ($def['parent'] !== null) {
        $parentId = db()->value("SELECT id FROM categories WHERE slug = ?", [$def['parent']]);
    }
    $existing = db()->value("SELECT id FROM categories WHERE slug = ?", [$def['slug']]);
    if ($existing) {
        db()->exec(
            "UPDATE categories SET name = ?, parent_id = ?, sort_order = ?, description = ? WHERE id = ?",
            [$def['name'], $parentId, $def['sort'], $desc[$def['slug']] ?? '', $existing]
        );
    } else {
        db()->exec(
            "INSERT INTO categories (slug, name, parent_id, sort_order, description) VALUES (?, ?, ?, ?, ?)",
            [$def['slug'], $def['name'], $parentId, $def['sort'], $desc[$def['slug']] ?? '']
        );
    }
}

$catSlugCache = [];
foreach (db()->run("SELECT id, slug FROM categories") as $row) {
    $catSlugCache[$row['slug']] = (int) $row['id'];
}

// ---------------- Product mapping ----------------
function categoryFor(array $p): ?string
{
    $group = $p['group'] ?? '';
    switch ($p['category'] ?? '') {
        case 'storage':
            return 'storage';
        case 'disks-ssd':
            return 'disks-ssd';
        case 'disks-sas':
            if (mb_stripos($group, 'Полк') !== false || mb_stripos($group, 'enclosure') !== false) {
                return 'enclosures';
            }
            return 'disks-sas';
        case 'networking':
            if (mb_stripos($group, 'Коммутатор') !== false) {
                return 'switches';
            }
            return 'routers';
        case 'io':
            return 'io';
        case 'optics':
            return 'optics';
        case 'accessories':
            if (mb_stripos($group, 'Полк') !== false) {
                return 'enclosures';
            }
            return null;
    }
    return null;
}

function stockStateFor(array $p): string
{
    if (($p['category'] ?? '') === 'storage') {
        return 'order';
    }
    if (isset($p['stock_state'])) {
        return $p['stock_state'];
    }
    $stock = (int) ($p['stock'] ?? 0);
    if ($stock === 0) {
        return 'order';
    }
    if ($stock <= 4) {
        return 'low';
    }
    return 'in';
}

$usedSlugs = [];
$seenSkus = [];
$inserted = 0;
$skipped = 0;

foreach ($productsRaw as $p) {
    $catSlug = categoryFor($p);
    if ($catSlug === null || !isset($catSlugCache[$catSlug])) {
        $skipped++;
        continue;
    }

    $sku = (string) ($p['sku'] ?? '');
    if ($sku !== '') {
        if (isset($seenSkus[$sku])) {
            $skipped++;
            continue;
        }
        $seenSkus[$sku] = true;
    }

    $slug = (string) ($p['id'] ?? '');
    if ($slug === '') {
        $slug = str_slug($p['title'] ?? 'item');
    }
    if (isset($usedSlugs[$slug])) {
        $slug = $slug . '-' . $usedSlugs[$slug]++;
    }
    $usedSlugs[$slug] = isset($usedSlugs[$slug]) ? $usedSlugs[$slug] + 1 : 1;

    $stock = (int) ($p['stock'] ?? 0);
    $stockState = stockStateFor($p);
    $price = isset($p['price']) ? (int) $p['price'] : null;
    $badge = (string) ($p['badge'] ?? '');
    if ($badge === '') {
        $badge = (string) implode(', ', array_slice($p['tags'] ?? [], 0, 3));
    }
    $badge = mb_substr($badge, 0, 64);

    $isHit = 0;
    $isNew = 0;
    $badgeLower = mb_strtolower($badge);
    if ($catSlug === 'storage') {
        $isHit = 1;
    } elseif (mb_stripos($badgeLower, 'хит') !== false) {
        $isHit = 1;
    } elseif (mb_stripos($badgeLower, 'нов') !== false) {
        $isNew = 1;
    }

    $specs = $p['specs'] ?? [];
    $configs = $p['configs'] ?? [];
    $tags = $p['tags'] ?? [];
    if (isset($p['capacity']) && $p['capacity'] !== '') {
        $specs['Ёмкость'] = ($p['capacity'] . ' TB');
    }
    if (isset($p['system']) && $p['system'] !== '') {
        $specs['Система'] = ucfirst((string) $p['system']);
    }

    db()->exec(
        "INSERT INTO products
         (category_id, sku, slug, title, subtitle, description, price, stock, stock_state, specs, configs, tags, group_label, badge, is_hit, is_new)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $catSlugCache[$catSlug],
            (string) ($p['sku'] ?? ''),
            $slug,
            (string) ($p['title'] ?? ''),
            (string) ($p['subtitle'] ?? ''),
            (string) ($p['desc'] ?? ''),
            $price,
            $stock,
            $stockState,
            json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($configs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode(array_values($tags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) ($p['group'] ?? ''),
            $badge,
            $isHit,
            $isNew,
        ]
    );
    $inserted++;
}

echo "Вставлено товаров: $inserted\n";
echo "Пропущено: $skipped\n";

// Category product counts
$counts = db()->run("SELECT c.slug, COUNT(p.id) AS n FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.slug ORDER BY c.sort_order, c.id");
foreach ($counts as $row) {
    echo sprintf("  %-14s %d\n", $row['slug'], $row['n']);
}
echo "Готово.\n";