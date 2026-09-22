<?php

declare(strict_types=1);

// Пример конфигурации. Скопируйте в src/config.php и при необходимости
// задайте свои значения через переменные окружения (SHOP_DB_*, SHOP_ADMIN_*).

return [
    'db' => [
        'host'    => getenv('SHOP_DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('SHOP_DB_PORT') ?: '3307',
        'name'    => getenv('SHOP_DB_NAME') ?: 'site_shop',
        'user'    => getenv('SHOP_DB_USER') ?: 'root',
        'pass'    => getenv('SHOP_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'name'         => 'HUAWEI Enterprise',
        'legal_name'   => 'HUAWEI Enterprise Reseller',
        'phone'        => '+7 (495) 120-30-40',
        'phone_intern' => '+74951203040',
        'email'        => 'info@huawei-enterprise.ru',
        'address'      => 'г. Москва, Складской проезд, д. 1, стр. 2',
        'hours'        => 'Пн–Пт: 9:00–18:00 (МСК)',
        'timezone'     => 'Europe/Moscow',
        'meta'         => 'Купить оригинальное оборудование Huawei: системы хранения данных OceanStor, NVMe-диски, коммутаторы, трансиверы, интерфейсные модули и аксессуары с гарантией производителя. Доставка по всей России.',
        'copyright'    => '© ' . date('Y') . ' HUAWEI Enterprise Reseller. Все права защищены. Huawei является зарегистрированной торговой маркой Huawei Technologies Co., Ltd.',
    ],
    'catalog' => [
        'per_page' => 12,
        'popular_prefixes' => [
            'storage' => ['D3V6-192G-NVMe'],
        ],
    ],
    'admin' => [
        'login'    => getenv('SHOP_ADMIN_LOGIN') ?: 'admin',
        'password' => getenv('SHOP_ADMIN_PASSWORD') ?: 'admin123',
    ],
    'import' => [
        'usd_rate'  => (float) (getenv('SHOP_IMPORT_USD_RATE') ?: '92.0'),
        'max_size'  => 20, // МБ
        'max_errors' => 50,
    ],
];