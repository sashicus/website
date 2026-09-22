<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;

final class ShopController
{
    private Category $categories;
    private Page $pages;
    private Product $products;
    private Order $orders;

    public function __construct()
    {
        $this->categories = new Category();
        $this->products = new Product();
        $this->orders = new Order();
        $this->pages = new Page();
    }

    public function home(): void
    {
        $tree = $this->categories->tree();
        $totalCount = $this->products->countAll();

        $sections = [];
        $homeSections = ['storage', 'disks', 'networking', 'io'];
        foreach ($tree as $root) {
            if (!in_array($root['slug'], $homeSections, true)) {
                continue;
            }
            $ids = $this->categories->idList((int) $root['id']);
            $items = $this->products->inCategories($ids, 4);
            if (!$items) {
                continue;
            }
            $count = (int) ($root['product_count'] ?? 0);
            foreach ($root['children'] as $child) {
                $count += (int) ($child['product_count'] ?? 0);
            }
            $sections[] = [
                'title' => $root['name'],
                'url'   => category_url($root),
                'count' => $count,
                'items' => $items,
            ];
        }

        view('pages/home', [
            'categories' => $tree,
            'sections'   => $sections,
            'totalCount' => $totalCount,
            'page'       => [
                'title'       => 'Поставка оборудования Huawei для бизнеса',
                'description' => 'Оригинальное серверное, сетевое оборудование и системы хранения данных Huawei. Официальные поставки, гарантия, доставка по России.',
            ],
        ]);
    }

    public function catalog(?string $categorySlug = null): void
    {
        $tree = $this->categories->tree();
        $f = [
            'q'            => query_param('q'),
            'category'     => $categorySlug ?? query_param('category'),
            'price_min'    => query_param('price_min'),
            'price_max'    => query_param('price_max'),
            'in_stock'     => isset($_GET['in_stock']),
            'sort'         => query_param('sort', 'new'),
            'page'         => max(1, (int) (query_param('page') ?: 1)),
        ];

        $categoryIds = [];
        $cat = null;
        if ($f['category'] !== '') {
            $cat = $this->categories->bySlug($f['category']);
            if ($cat) {
                $categoryIds = $this->categories->idList((int) $cat['id']);
            } else {
                http_response_code(404);
                view('pages/404', ['categories' => $tree]);
                return;
            }
        }

        [$items, $total, $page, $perPage] = $this->products->search([
            'q'            => $f['q'],
            'category_ids' => $categoryIds,
            'price_min'    => $f['price_min'] !== '' ? (int) $f['price_min'] : null,
            'price_max'    => $f['price_max'] !== '' ? (int) $f['price_max'] : null,
            'in_stock'     => $f['in_stock'],
            'sort'         => $f['sort'],
            'per_page'     => config('catalog.per_page', 12),
            'page'         => $f['page'],
        ]);

        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
        if ($f['page'] > $totalPages) {
            $qsParts = array_filter([
                'q'         => $f['q'],
                'price_min' => $f['price_min'],
                'price_max' => $f['price_max'],
                'in_stock'  => $f['in_stock'] ? '1' : '',
                'sort'      => $f['sort'] === 'new' ? '' : $f['sort'],
            ], static fn ($v): bool => $v !== '' && $v !== null);
            $qs = http_build_query($qsParts);
            $basePath = $f['category'] !== '' ? '/' . $f['category'] : '/search';
            redirect($basePath . ($qs !== '' ? '?' . $qs : ''));
        }

        [$minPrice, $maxPrice] = $this->products->minMaxPrices();

        $pageTitle = $f['q'] !== '' ? 'Поиск: ' . $f['q'] : ($cat !== null ? $cat['name'] : 'Каталог оборудования');
        $metaTitle = $pageTitle;
        $metaDescription = $pageTitle . ' — купить с доставкой по России';
        if ($cat !== null) {
            if (($cat['meta_title'] ?? '') !== '') {
                $metaTitle = $cat['meta_title'];
            }
            if (($cat['meta_description'] ?? '') !== '') {
                $metaDescription = $cat['meta_description'];
            }
        }

        view('pages/catalog', [
            'categories'  => $tree,
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'f'           => $f,
            'currentCat'  => $cat,
            'catChain'    => $cat !== null ? $this->categories->chain((int) $cat['id']) : [],
            'minPrice'    => $minPrice,
            'maxPrice'    => $maxPrice,
            'meta'        => [
                'title'       => $metaTitle,
                'description' => $metaDescription,
                'active_cat'  => $cat !== null ? $cat['slug'] : '',
            ],
        ]);
    }

    public function product(string $categorySlug, string $productSlug): void
    {
        $item = $this->products->bySlug($productSlug);
        if ($item === null || ($item['category_slug'] ?? '') !== $categorySlug) {
            http_response_code(404);
            view('pages/404', ['categories' => $this->categories->tree()]);
            return;
        }
        view('pages/product', [
            'categories' => $this->categories->tree(),
            'product'    => $item,
            'gallery'    => $this->products->gallery((int) $item['id']),
            'catChain'   => $this->categories->chain((int) $item['category_id']),
            'related'    => $this->products->related($item, 4),
        ]);
    }

    /**
     * 301-reirect старого URL /product/{slug} → /{category}/{slug}
     */
    public function productRedirect(string $slug): void
    {
        $item = $this->products->bySlug($slug);
        if ($item === null || ($item['category_slug'] ?? '') === '') {
            $this->notFound();
            return;
        }
        redirect('/' . $item['category_slug'] . '/' . $item['slug'], 301);
    }

    /**
     * 301-reirect старого URL /catalog/{slug} → /{slug}
     */
    public function catalogRedirect(string $categorySlug): void
    {
        $cat = $this->categories->bySlug($categorySlug);
        if ($cat === null) {
            $this->notFound();
            return;
        }
        redirect('/' . $cat['slug'], 301);
    }

    public function search(): void
    {
        $this->catalog();
    }

    public function cart(): void
    {
        view('pages/cart', [
            'categories' => $this->categories->tree(),
        ]);
    }

    public function contacts(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleContactForm();
            redirect('/contacts?sent=1');
        }
        $this->page('contacts');
    }

    private function handleContactForm(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($name === '' || $phone === '') {
            return;
        }

        try {
            $to = config('site.email', '');
            if ($to !== '' && function_exists('mail')) {
                $body = "Имя: {$name}\nТелефон: {$phone}\n\n{$message}";
                @mail($to, 'Сообщение с сайта — ' . config('site.name'), $body, 'Content-Type: text/plain; charset=utf-8');
            }
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function about(): void
    {
        $this->page('about');
    }

    /**
     * Динамический путь из одного сегмента: категория или страница из БД.
     */
    public function catalogOrPage(string $slug): void
    {
        if ($this->categories->bySlug($slug) !== null) {
            $this->catalog($slug);
            return;
        }
        $this->page($slug);
    }

    /**
     * Страница из БД (about, contacts и любые добавленные в админке).
     */
    public function page(string $slug): void
    {
        $page = $this->pages->bySlugActive($slug);
        if ($page === null) {
            $this->notFound();
            return;
        }
        view('pages/page', [
            'categories' => $this->categories->tree(),
            'syspage'    => $page,
        ]);
    }

    public function orderSuccess(): void
    {
        view('pages/order-success', [
            'categories' => $this->categories->tree(),
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);
        view('pages/404', [
            'categories' => $this->categories->tree(),
        ]);
    }

    // ---------------- API ----------------

    public function apiSearch(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q === '') {
            json_response([]);
        }
        $rows = db()->run(
            "SELECT p.slug, p.title, p.sku, p.price, p.stock_state,
                    c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE CONCAT_WS(' ', p.title, p.subtitle, p.sku, p.description) LIKE :q
             ORDER BY p.is_hit DESC, p.id
             LIMIT 8",
            ['q' => '%' . $q . '%']
        );
        json_response($rows);
    }

    public function apiCart(): void
    {
        $slugs = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($_GET['slugs'] ?? ''))))));
        if (!$slugs) {
            $idsParam = $_GET['ids'] ?? '';
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsParam)))));
            if (!$ids) {
                json_response([]);
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $rows = db()->run(
                "SELECT id, slug, sku, title, price, old_price, stock, stock_state,
                        subtitle, category_id, category_slug
                 FROM products WHERE id IN ($placeholders)",
                $ids
            );
            json_response($rows);
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $rows = db()->run(
            "SELECT p.id, p.slug, p.sku, p.title, p.price, p.old_price, p.stock, p.stock_state,
                    p.subtitle, p.category_id, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.slug IN ($placeholders)",
            $slugs
        );
        json_response($rows);
    }

    public function apiOrder(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'error' => 'method'], 405);
        }

        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $name  = trim((string) ($input['name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $company = trim((string) ($input['company'] ?? ''));
        $comment = trim((string) ($input['comment'] ?? ''));
        $items = $input['items'] ?? [];

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Укажите имя';
        }
        if (mb_strlen($phone) < 6) {
            $errors['phone'] = 'Укажите корректный телефон';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный e-mail';
        }
        if (!is_array($items) || count($items) === 0) {
            $errors['items'] = 'Корзина пуста';
        }
        if ($errors) {
            json_response(['ok' => false, 'errors' => $errors], 422);
        }

        $lines = [];
        $total = 0;
        foreach ($items as $line) {
            $qty = max(1, min(999, (int) ($line['qty'] ?? 1)));
            if (!empty($line['slug'])) {
                $slug = trim((string) $line['slug']);
                $product = $slug !== '' ? $this->products->bySlug($slug) : null;
            } else {
                $id = (int) ($line['id'] ?? 0);
                $product = $id > 0 ? $this->products->find($id) : null;
            }
            if ($product === null) {
                continue;
            }
            if ($product['price'] === null || $product['price'] === 0) {
                continue;
            }
            $lines[] = [
                'id'    => $product['id'],
                'sku'   => $product['sku'],
                'title' => $product['title'],
                'price' => (int) $product['price'],
                'qty'   => $qty,
            ];
            $total += (int) $product['price'] * $qty;
        }

        if (!$lines) {
            json_response(['ok' => false, 'errors' => ['items' => 'В заказе нет доступных товаров']], 422);
        }

        $orderId = $this->orders->create([
            'name'    => $name,
            'phone'   => $phone,
            'email'   => $email,
            'company' => $company,
            'comment' => $comment,
            'items'   => $lines,
            'total'   => $total,
        ]);

        // Best-effort email notification; never breaks the order flow.
        try {
            $this->notifyOrder($orderId, $name, $phone, $email, $total, $lines, $comment);
        } catch (\Throwable $e) {
            // ignore
        }

        json_response(['ok' => true, 'order_id' => $orderId, 'total' => $total]);
    }

    private function notifyOrder(int $orderId, string $name, string $phone, string $email, int $total, array $lines, string $comment): void
    {
        $to = config('site.email', '');
        if ($to === '') {
            return;
        }
        $siteName = config('site.name');
        $rows = '';
        foreach ($lines as $line) {
            $rows .= "{$line['title']} ({$line['sku']}) × {$line['qty']} — " . money($line['price'] * $line['qty']) . "\n";
        }
        $subject = "Новый заказ №{$orderId} — {$siteName}";
        $body = "Заказ №{$orderId}\n\nИмя: {$name}\nТелефон: {$phone}\nE-mail: {$email}\n\n{$rows}\nИтого: " . money($total) . "\n\nКомментарий: {$comment}";
        $headers = 'Content-Type: text/plain; charset=utf-8' . "\r\n";
        $headers .= 'From: ' . $siteName . ' <' . $to . '>' . "\r\n";
        if (function_exists('mail')) {
            @mail($to, $subject, $body, $headers);
        }
    }

    public function sitemap(): void
    {
        $products = db()->run(
            "SELECT p.slug, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id"
        );
        $categories = db()->run("SELECT slug FROM categories WHERE is_active = 1 ORDER BY id");
        $pages = db()->run("SELECT slug FROM pages WHERE is_active = 1 ORDER BY id");
        $static = ['', 'cart']; // contacts/about теперь в pages

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($static as $path) {
            echo '<url><loc>' . e(url($path === '' ? '/' : $path)) . '</loc></url>' . "\n";
        }
        foreach ($pages as $pg) {
            echo '<url><loc>' . e(url('/' . $pg['slug'])) . '</loc></url>' . "\n";
        }
        foreach ($categories as $cat) {
            echo '<url><loc>' . e(url('/' . $cat['slug'])) . '</loc></url>' . "\n";
        }
        foreach ($products as $product) {
            echo '<url><loc>' . e(url('/' . $product['category_slug'] . '/' . $product['slug'])) . '</loc></url>' . "\n";
        }
        echo '</urlset>';
    }

    public function apiSuggest(): void
    {
        $this->apiSearch();
    }
}