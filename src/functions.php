<?php

declare(strict_types=1);

use App\Core\Database;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/', array $query = []): string
    {
        $base = app('base');
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return $query ? $base . '/?' . http_build_query($query, '', '&') : $base . '/';
        }
        $url = $base . rtrim($path, '/');
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&');
        }
        return $url;
    }
}

if (!function_exists('product_url')) {
    /**
     * SEO-URL товара: /{category}/{slug}
     */
    function product_url(array $product): string
    {
        $cat = $product['category_slug'] ?? '';
        $slug = $product['slug'] ?? '';
        return ($cat !== '' && $slug !== '') ? url('/' . $cat . '/' . $slug) : url('/');
    }
}

if (!function_exists('category_url')) {
    /**
     * SEO-URL категории: /{slug}
     */
    function category_url(array|string $category): string
    {
        $slug = is_array($category) ? ($category['slug'] ?? '') : $category;
        return $slug !== '' ? url('/' . $slug) : url('/');
    }
}

if (!function_exists('site_menu')) {
    /**
     * Пункты меню из таблицы menu_items по позиции:
     *  - 'top'            — верхнее меню (шапка);
     *  - 'footer-catalog' — первое нижнее меню (футер, ссылки на каталог);
     *  - 'footer-info'    — второе нижнее меню (футер, ссылки для покупателей).
     * Пункты-категории ссылаются на /{slug}; остальные — произвольные ссылки.
     * Если меню пусто, отдаётся резервный набор ссылок по умолчанию.
     */
    function site_menu(string $position = 'top'): array
    {
        $rows = (new \App\Models\Menu())->active($position);
        $items = [];
        foreach ($rows as $row) {
            $isCategory = ($row['category_id'] ?? null) !== null;
            $items[] = [
                'label'       => ($row['category_name'] ?? '') !== '' ? $row['category_name'] : $row['label'],
                'url'         => $isCategory ? url('/' . $row['category_slug']) : (str_starts_with($row['url'], 'http') ? $row['url'] : url($row['url'])),
                'active_slug' => $isCategory ? (string) $row['category_slug'] : '',
            ];
        }
        if ($items) {
            return $items;
        }

        if ($position === 'footer-info') {
            return [
                ['label' => 'Поиск товаров', 'url' => url('/search'), 'active_slug' => ''],
                ['label' => 'О компании', 'url' => url('/about'), 'active_slug' => ''],
                ['label' => 'Контакты', 'url' => url('/contacts'), 'active_slug' => ''],
                ['label' => 'Корзина', 'url' => url('/cart'), 'active_slug' => ''],
                ['label' => 'Карта сайта', 'url' => url('/sitemap.xml'), 'active_slug' => ''],
            ];
        }

        // top и footer-catalog без своих пунктов показывают корневые категории
        foreach ((new \App\Models\Category())->tree() as $cat) {
            if (($cat['parent_id'] ?? null) !== null) {
                continue;
            }
            $items[] = [
                'label'       => $cat['name'],
                'url'         => category_url($cat),
                'active_slug' => $cat['slug'],
            ];
        }
        return $items;
    }
}

if (!function_exists('money')) {
    /**
     * 1234567 -> "1 234 567 ₽"
     */
    function money(?int $amount, bool $withSign = true): string
    {
        if ($amount === null) {
            return 'по запросу';
        }
        $formatted = number_format($amount, 0, ',', ' ');
        return $withSign ? $formatted . ' ₽' : $formatted;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): never
    {
        header('Location: ' . url($path), true, $code);
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('query_param')) {
    function query_param(string $key, string $default = ''): string
    {
        return trim((string) ($_GET[$key] ?? $default));
    }
}

if (!function_exists('str_slug')) {
    function str_slug(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9-]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? $text : 'item';
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): void
    {
        $view = $data;
        extract($view, EXTR_SKIP);
        $file = APP_ROOT . '/templates/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'template not found: ' . e($template);
            return;
        }
        require $file;
    }
}

if (!function_exists('render')) {
    function render(string $partial, array $data = []): string
    {
        ob_start();
        view($partial, $data);
        return (string) ob_get_clean();
    }
}

if (!function_exists('stock_label')) {
    function stock_label(array $product): string
    {
        return match ($product['stock_state'] ?? 'in') {
            'in'    => 'В наличии',
            'low'   => 'Мало на складе',
            'order' => 'Под заказ',
            default => 'Нет в наличии',
        };
    }
}

if (!function_exists('stock_class')) {
    function stock_class(array $product): string
    {
        return 'stock--' . ($product['stock_state'] ?? 'in');
    }
}

if (!function_exists('decode_json')) {
    function decode_json(?string $json): array
    {
        if ($json === null || $json === '' || $json === 'null') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_check')) {
    /**
     * Проверка CSRF для POST-запросов админки. Завершает запрос при несовпадении.
     */
    function csrf_check(): void
    {
        $token = (string) ($_POST['_csrf'] ?? '');
        $stored = (string) ($_SESSION['_csrf'] ?? '');
        if ($token === '' || $stored === '' || !hash_equals($stored, $token)) {
            http_response_code(419);
            exit('Проверка CSRF не пройдена. Обновите страницу и попробуйте снова.');
        }
    }
}

if (!function_exists('admin_authed')) {
    function admin_authed(): bool
    {
        return !empty($_SESSION['admin_authed']);
    }
}

if (!function_exists('product_img_url')) {
    /**
     * URL главного фото товара (или заглушки, если фото нет).
     */
    function product_img_url(array $product): string
    {
        $file = (string) ($product['image'] ?? '');
        return $file !== '' ? url('/uploads/products/' . $file) : url('/assets/img/no-photo.svg');
    }
}

if (!function_exists('page_title')) {
    function page_title(string $title): string
    {
        $site = config('site.name', '');
        return $title !== '' ? $title . ' — ' . $site : $site;
    }
}

if (!function_exists('product_title_overrides')) {
    /**
     * Карта переопределения заголовков товаров: sku => чистый заголовок.
     * Файл в data/overrides/product-titles.json, кэшируется на запрос.
     */
    function product_title_overrides(): array
    {
        static $map = null;
        if ($map === null) {
            $file = dirname(__DIR__) . '/data/overrides/product-titles.json';
            $map = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }
        return $map;
    }
}

if (!function_exists('clean_product_title')) {
    /**
     * Эвристическая чистка сырого заголовка: пробелы, EAN, хвостовые (код) и пунктуация.
     */
    function clean_product_title(string $title): string
    {
        $s = trim($title);
        if ($s === '') {
            return $s;
        }
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\bEAN:?\s*[0-9]{8,}\b/i', '', $s) ?? $s;
        $s = preg_replace('/\bSKU:?\s*\S+/i', '', $s) ?? $s;
        do {
            $next = preg_replace('/\s*\(\s*[^()]+\s*\)\s*$/u', '', $s);
            if ($next === null || $next === $s) {
                break;
            }
            $s = trim($next);
        } while (true);
        $s = rtrim($s, ".,;:- ");
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        if (mb_strlen($s, 'UTF-8') > 250) {
            $s = mb_substr($s, 0, 250, 'UTF-8');
        }
        return trim($s);
    }
}

if (!function_exists('normalize_product_title')) {
    /**
     * Финальный заголовок для импорта: сначала карта переопределения (по sku),
     * затем эвристическая чистка исходного текста.
     */
    function normalize_product_title(string $title, string $sku = ''): string
    {
        $t = trim($title);
        if ($sku !== '') {
            $overrides = product_title_overrides();
            $override  = trim((string) ($overrides[$sku] ?? ''));
            if ($override !== '') {
                return $override;
            }
        }
        return clean_product_title($t);
    }
}

if (!function_exists('is_current')) {
    function is_current(string $path): bool
    {
        return ($_SERVER['REQUEST_URI'] ?? '/') === url($path) || (($_SERVER['REQUEST_URI'] ?? '') === rtrim(url($path), '/') . '/');
    }
}