<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Spreadsheet;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;

final class AdminController
{
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const IMPORT_EXT = ['xlsx', 'csv'];

    private const MENU_POSITIONS = [
        'top'             => 'Верхнее меню',
        'footer-catalog'  => 'Футер: каталог товаров',
        'footer-info'     => 'Футер: покупателям',
    ];

    private Category $categories;
    private Menu $menu;
    private Page $pages;
    private Product $products;

    public function __construct()
    {
        $this->categories = new Category();
        $this->menu = new Menu();
        $this->pages = new Page();
        $this->products = new Product();
    }

    /**
     * Роутер админки. $segments = ['admin', ...rest].
     */
    public function handle(string $method, array $segments): void
    {
        $sub = $segments[0] ?? '';

        if ($sub === 'login') {
            $this->login();
            return;
        }
        if ($sub === 'logout') {
            $this->logout();
            return;
        }

        $this->requireAuth();

        if ($sub === '' || ($sub === 'products' && empty($segments[1]))) {
            if ($method === 'POST') {
                redirect('/admin/products');
            }
            $this->index();
            return;
        }

        if ($sub === 'products' && ($segments[1] ?? '') === 'new') {
            if ($method === 'POST') {
                $this->store();
                return;
            }
            $this->create();
            return;
        }

        if ($sub === 'products' && isset($segments[1]) && ctype_digit($segments[1])) {
            $id = (int) $segments[1];
            $action = $segments[2] ?? 'edit';

            switch ($action) {
                case 'edit':
                    if ($method === 'POST') {
                        $this->update($id);
                    } else {
                        $this->edit($id);
                    }
                    return;

                case 'delete':
                    if ($method === 'POST') {
                        $this->destroy($id);
                    }
                    redirect('/admin/products');

                case 'gallery':
                    $this->gallery($id);
                    return;

                case 'images':
                    if ($method === 'POST' && !isset($segments[3])) {
                        $this->uploadImages($id);
                        redirect('/admin/products/' . $id . '/gallery');
                    }
                    $imageId = (int) ($segments[3] ?? 0);
                    $op = $segments[4] ?? '';
                    if ($method === 'POST' && $op === 'main') {
                        $this->setMainImage($id, $imageId);
                    }
                    if ($method === 'POST' && $op === 'delete') {
                        $this->deleteImage($id, $imageId);
                    }
                    redirect('/admin/products/' . $id . '/gallery');
            }

            $this->adminError('Неизвестное действие');
        }

        if ($sub === 'import') {
            $action = $segments[1] ?? '';

            if ($method === 'POST' && ($action === '' || $action === 'preview' || $action === 'run' || $action === 'cancel')) {
                switch ($action) {
                    case '':
                        $this->importUpload();
                        return;
                    case 'preview':
                        $this->importPreview();
                        return;
                    case 'run':
                        $this->importRun();
                        return;
                    case 'cancel':
                        $this->importCancel();
                        return;
                }
            }

            if ($action === '' && $method === 'GET') {
                $this->importIndex();
                return;
            }

            $this->adminError('Неизвестное действие');
        }

        if ($sub === 'categories' && empty($segments[1])) {
            $this->categoriesIndex();
            return;
        }

        if ($sub === 'categories' && $segments[1] === 'new') {
            if ($method === 'POST') {
                $this->categoryStore();
                return;
            }
            $this->categoryCreate();
            return;
        }

        if ($sub === 'categories' && ($segments[1] ?? '') === 'sort') {
            if ($method === 'POST') {
                $this->sortCategories();
            }
            redirect('/admin/categories');
        }

        if ($sub === 'categories' && isset($segments[1]) && ctype_digit($segments[1])) {
            $id = (int) $segments[1];
            $action = $segments[2] ?? 'edit';

            switch ($action) {
                case 'edit':
                    if ($method === 'POST') {
                        $this->categoryUpdate($id);
                    } else {
                        $this->categoryEdit($id);
                    }
                    return;

                case 'delete':
                    if ($method === 'POST') {
                        $this->categoryDestroy($id);
                    }
                    redirect('/admin/categories');

                case 'toggle':
                    if ($method === 'POST') {
                        $this->categoryToggle($id);
                    }
                    redirect('/admin/categories');
            }

            $this->adminError('Неизвестное действие');
        }

        if ($sub === 'menu' && empty($segments[1])) {
            $this->menuIndex();
            return;
        }

        if ($sub === 'menu' && $segments[1] === 'new') {
            if ($method === 'POST') {
                $this->menuStore();
                return;
            }
            $this->menuCreate();
            return;
        }

        if ($sub === 'menu' && ($segments[1] ?? '') === 'sort') {
            if ($method === 'POST') {
                $this->sortMenu();
            }
            redirect('/admin/menu');
        }

        if ($sub === 'menu' && isset($segments[1]) && ctype_digit($segments[1])) {
            $id = (int) $segments[1];
            $action = $segments[2] ?? 'edit';

            switch ($action) {
                case 'edit':
                    if ($method === 'POST') {
                        $this->menuUpdate($id);
                    } else {
                        $this->menuEdit($id);
                    }
                    return;

                case 'delete':
                    if ($method === 'POST') {
                        $this->menuDestroy($id);
                    }
                    redirect('/admin/menu');

                case 'toggle':
                    if ($method === 'POST') {
                        $this->menuToggle($id);
                    }
                    redirect('/admin/menu');
            }

            $this->adminError('Неизвестное действие');
        }

        if ($sub === 'pages' && empty($segments[1])) {
            $this->pagesIndex();
            return;
        }

        if ($sub === 'pages' && $segments[1] === 'new') {
            if ($method === 'POST') {
                $this->pageStore();
                return;
            }
            $this->pageCreate();
            return;
        }

        if ($sub === 'pages' && isset($segments[1]) && ctype_digit($segments[1])) {
            $id = (int) $segments[1];
            $action = $segments[2] ?? 'edit';

            switch ($action) {
                case 'edit':
                    if ($method === 'POST') {
                        $this->pageUpdate($id);
                    } else {
                        $this->pageEdit($id);
                    }
                    return;

                case 'delete':
                    if ($method === 'POST') {
                        $this->pageDestroy($id);
                    }
                    redirect('/admin/pages');

                case 'toggle':
                    if ($method === 'POST') {
                        $this->pageToggle($id);
                    }
                    redirect('/admin/pages');
            }

            $this->adminError('Неизвестное действие');
        }

        $this->adminError('Страница админки не найдена');
    }

    // ---------------- Auth ----------------

    private function login(): void
    {
        if (admin_authed()) {
            redirect('/admin');
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $cfg = config('admin', []);
            $login = trim((string) ($_POST['login'] ?? ''));
            $pass = (string) ($_POST['password'] ?? '');
            if (
                $login !== '' &&
                hash_equals((string) ($cfg['login'] ?? ''), $login) &&
                hash_equals((string) ($cfg['password'] ?? ''), $pass)
            ) {
                $_SESSION['admin_authed'] = 1;
                session_regenerate_id(true);
                redirect('/admin');
            }
            $error = 'Неверный логин или пароль';
        }

        $this->render('login', [
            'error' => $error,
        ]);
    }

    private function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        redirect('/admin/login');
    }

    private function requireAuth(): void
    {
        if (!admin_authed()) {
            redirect('/admin/login');
        }
    }

    // ---------------- Products ----------------

    private function index(): void
    {
        $f = [
            'q'        => query_param('q'),
            'category' => query_param('category'),
            'page'     => max(1, (int) (query_param('page') ?: 1)),
        ];

        $categoryIds = [];
        $currentCat = null;
        if ($f['category'] !== '') {
            $currentCat = $this->categories->bySlug($f['category']);
            if ($currentCat !== null) {
                $categoryIds = $this->categories->idList((int) $currentCat['id']);
            }
        }

        [$items, $total, $page, $perPage] = $this->products->search([
            'q'            => $f['q'],
            'category_ids' => $categoryIds,
            'per_page'     => 20,
            'page'         => $f['page'],
        ]);
        $totalPages = max(1, (int) ceil($total / max(1, $perPage)));

        $this->render('products', [
            'items'       => $items,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'f'           => $f,
            'currentCat'  => $currentCat,
            'tree'        => $this->categories->tree(),
        ]);
    }

    private function create(): void
    {
        $this->render('product-form', [
            'product'    => null,
            'tree'       => $this->categories->tree(),
            'errors'     => [],
            'old'        => [],
        ]);
    }

    private function store(): void
    {
        csrf_check();
        $data = $this->collectData();
        $errors = $this->validate($data);

        if (!$errors) {
            $slug = $this->uniqueSlug($data['slug'], 0);
            try {
                db()->exec(
                    "INSERT INTO products
                        (category_id, sku, slug, title, subtitle, description, meta_title, meta_description,
                         price, old_price, stock, stock_state, specs, configs, tags, group_label, badge, image,
                         is_hit, is_new, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())",
                    [
                        $data['category_id'], $data['sku'], $slug, $data['title'], $data['subtitle'],
                        $data['description'], $data['meta_title'], $data['meta_description'],
                        $data['price'], $data['old_price'],
                        $data['stock'], $data['stock_state'], $data['specs_json'], $data['configs_json'],
                        $data['tags_json'], $data['group_label'], $data['badge'],
                        '', $data['is_hit'], $data['is_new'],
                    ]
                );
                $id = (int) db()->lastId();
                $this->saveMainImage($id, $data['main_image']);
                redirect('/admin/products/' . $id . '/edit?created=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('product-form', [
            'product' => null,
            'tree'    => $this->categories->tree(),
            'errors'  => $errors,
            'old'     => $data,
        ]);
    }

    private function edit(int $id): void
    {
        $product = $this->products->find($id);
        if ($product === null) {
            $this->adminError('Товар не найден');
        }

        $this->render('product-form', [
            'product'    => $product,
            'tree'       => $this->categories->tree(),
            'errors'     => [],
            'old'        => [],
            'created'    => isset($_GET['created']),
        ]);
    }

    private function update(int $id): void
    {
        csrf_check();
        $product = $this->products->find($id);
        if ($product === null) {
            $this->adminError('Товар не найден');
        }

        $data = $this->collectData();
        $errors = $this->validate($data);

        if (!$errors) {
            $slug = $this->uniqueSlug($data['slug'], $id);
            try {
                db()->exec(
                    "UPDATE products SET
                        category_id = ?, sku = ?, slug = ?, title = ?, subtitle = ?,
                        description = ?, meta_title = ?, meta_description = ?, price = ?, old_price = ?,
                        stock = ?, stock_state = ?, specs = ?, configs = ?, tags = ?, group_label = ?, badge = ?,
                        is_hit = ?, is_new = ?, updated_at = NOW()
                     WHERE id = ?",
                    [
                        $data['category_id'], $data['sku'], $slug, $data['title'], $data['subtitle'],
                        $data['description'], $data['meta_title'], $data['meta_description'], $data['price'], $data['old_price'],
                        $data['stock'], $data['stock_state'], $data['specs_json'], $data['configs_json'],
                        $data['tags_json'], $data['group_label'], $data['badge'],
                        $data['is_hit'], $data['is_new'], $id,
                    ]
                );
                $this->saveMainImage($id, $data['main_image']);
                redirect('/admin/products/' . $id . '/edit?saved=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('product-form', [
            'product' => $this->products->find($id),
            'tree'    => $this->categories->tree(),
            'errors'  => $errors,
            'old'     => $data,
        ]);
    }

    private function destroy(int $id): void
    {
        csrf_check();
        $product = $this->products->find($id);
        if ($product !== null && ($product['image'] ?? '') !== '') {
            $this->deleteUploadedFile((string) $product['image']);
        }
        db()->exec('DELETE FROM products WHERE id = ?', [$id]);
        redirect('/admin/products?deleted=1');
    }

    // ---------------- Import ----------------

    private function importIndex(): void
    {
        $state = $this->importState();

        if (isset($_GET['done'])) {
            $report = $state['report'] ?? null;
            unset($_SESSION['adm_import']['report']);
            $this->render('import', ['mode' => 'report', 'report' => $report]);
            return;
        }

        if (isset($_GET['preview']) && !empty($state['file'])) {
            $this->renderImportPreview($state);
            return;
        }

        $this->render('import', [
            'mode'    => 'upload',
            'allowed' => self::IMPORT_EXT,
            'maxSize' => (int) config('import.max_size', 20),
        ]);
    }

    private function importUpload(): void
    {
        csrf_check();
        $file = $_FILES['pricelist'] ?? null;
        if (!is_array($file) || empty($file['tmp_name']) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            redirect('/admin/import?error=upload');
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMPORT_EXT, true)) {
            redirect('/admin/import?error=ext');
        }

        $maxSize = (int) config('import.max_size', 20) * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > $maxSize) {
            redirect('/admin/import?error=size');
        }

        $dir = $this->importDir();
        $filename = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!@move_uploaded_file((string) $file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $filename)) {
            redirect('/admin/import?error=upload');
        }

        try {
            $table = Spreadsheet::read($dir . DIRECTORY_SEPARATOR . $filename);
        } catch (\Throwable $e) {
            @unlink($dir . DIRECTORY_SEPARATOR . $filename);
            redirect('/admin/import?error=parse');
        }

        if (!$table['rows']) {
            @unlink($dir . DIRECTORY_SEPARATOR . $filename);
            redirect('/admin/import?error=empty');
        }

        $map = $this->detectImportMap($table['headers']);

        $this->saveImportState([
            'file'     => $filename,
            'original' => (string) ($file['name'] ?? ''),
            'headers'  => $table['headers'],
            'total'    => count($table['rows']),
            'opts'     => $this->defaultImportOpts($map),
        ]);
        redirect('/admin/import?preview=1');
    }

    private function importPreview(): void
    {
        csrf_check();
        $this->saveImportOptsFromPost();
        if (($_POST['submit'] ?? 'preview') === 'run') {
            $this->importRun();
            return;
        }
        redirect('/admin/import?preview=1');
    }

    private function importRun(): void
    {
        $state = $this->importState();
        $path = $this->importDir() . DIRECTORY_SEPARATOR . (string) ($state['file'] ?? '');
        if (!is_file($path)) {
            $this->clearImportFile();
            redirect('/admin/import?error=session');
        }

        $opts = $this->currentImportOpts($state);
        if (($opts['map']['sku'] ?? null) === null) {
            redirect('/admin/import?preview=1&error=sku');
        }

        try {
            $table = Spreadsheet::read($path);
        } catch (\Throwable $e) {
            $this->clearImportFile();
            redirect('/admin/import?error=parse');
        }

        $report = $this->executeImport($table['rows'], $opts);
        $this->clearImportFile();
        $this->saveImportState(['report' => $report]);
        redirect('/admin/import?done=1');
    }

    private function importCancel(): void
    {
        csrf_check();
        $this->clearImportFile();
        redirect('/admin/import');
    }

    private function renderImportPreview(array $state): void
    {
        $dir = $this->importDir();
        $path = $dir . DIRECTORY_SEPARATOR . (string) ($state['file'] ?? '');
        if (!is_file($path)) {
            $this->clearImportFile();
            redirect('/admin/import?error=session');
        }

        try {
            $table = Spreadsheet::read($path);
        } catch (\Throwable $e) {
            $this->clearImportFile();
            redirect('/admin/import?error=parse');
        }

        $opts = $this->currentImportOpts($state);

        $preview = [];
        foreach (array_slice($table['rows'], 0, 20) as $row) {
            $rec = $this->buildImportRow($row, $opts, true);
            $rec['category_label'] = $this->importCategoryLabel($rec, $opts);
            $preview[] = $rec;
        }

        $this->render('import', [
            'mode'    => 'preview',
            'state'   => $state,
            'headers' => $state['headers'] ?? [],
            'opts'    => $opts,
            'preview' => $preview,
            'total'   => (int) ($state['total'] ?? count($table['rows'])),
        ]);
    }

    /**
     * Параметры импорта после формы предпросмотра (сохраняются в сессии).
     */
    private function saveImportOptsFromPost(): void
    {
        $state = $this->importState();
        $opts = $this->currentImportOpts($state);
        $headers = $state['headers'] ?? [];
        $n = count($headers);

        $map = [];
        foreach (['sku' => 'map_sku', 'name' => 'map_name', 'brand' => 'map_brand', 'qty' => 'map_qty', 'price' => 'map_price'] as $key => $field) {
            $idx = (int) ($_POST[$field] ?? -1);
            $map[$key] = ($idx >= 0 && $idx < $n) ? $idx : null;
        }

        $opts['map'] = $map;
        $opts['currency'] = ($_POST['currency'] ?? 'rub') === 'usd' ? 'usd' : 'rub';
        $rate = (float) str_replace(',', '.', trim((string) ($_POST['rate'] ?? '')));
        $opts['rate'] = (is_finite($rate) && $rate > 0) ? $rate : (float) config('import.usd_rate', 92.0);
        $opts['title_mode'] = ($_POST['title_mode'] ?? 'full') === 'after_comma' ? 'after_comma' : 'full';
        $opts['price_zero_null'] = isset($_POST['price_zero_null']);
        $opts['category_mode'] = in_array($_POST['category_mode'] ?? 'name', ['name', 'brand', 'single'], true)
            ? (string) $_POST['category_mode']
            : 'name';
        $opts['parent_id'] = max(0, (int) ($_POST['parent_id'] ?? 0));
        $opts['category_id'] = max(0, (int) ($_POST['category_id'] ?? 0));
        $opts['update_existing'] = isset($_POST['update_existing']);
        $opts['set_old_price'] = isset($_POST['set_old_price']);

        $state['opts'] = $opts;
        $this->saveImportState($state);
    }

    private function currentImportOpts(array $state): array
    {
        $defaults = [
            'map'              => ['sku' => null, 'name' => null, 'brand' => null, 'qty' => null, 'price' => null],
            'currency'         => 'rub',
            'rate'             => (float) config('import.usd_rate', 92.0),
            'title_mode'       => 'full',
            'price_zero_null'  => true,
            'category_mode'    => 'name',
            'parent_id'        => 0,
            'category_id'      => 0,
            'update_existing'  => true,
            'set_old_price'    => true,
        ];
        $opts = $state['opts'] ?? [];
        return is_array($opts) ? array_replace($defaults, $opts) : $defaults;
    }

    private function defaultImportOpts(array $map): array
    {
        return array_replace($this->currentImportOpts([]), [
            'map' => [
                'sku'   => $map['sku'],
                'name'  => $map['name'],
                'brand' => $map['brand'],
                'qty'   => $map['qty'],
                'price' => $map['price'],
            ],
            'currency' => $map['currency'] ?? 'rub',
        ]);
    }

    /**
     * Автоматическое определение колонок по заголовкам первого файла.
     */
    private function detectImportMap(array $headers): array
    {
        $detected = ['sku' => null, 'name' => null, 'brand' => null, 'qty' => null, 'price' => null, 'currency' => 'rub'];

        foreach ($headers as $i => $header) {
            $h = mb_strtolower(trim((string) $header), 'UTF-8');
            if ($h === '') {
                continue;
            }
            if ($detected['sku'] === null && preg_match('/артикул|sku|каталожн|модель/u', $h)) {
                $detected['sku'] = $i;
            } elseif ($detected['name'] === null && preg_match('/наименован|назван|товар|описан/u', $h)) {
                $detected['name'] = $i;
            } elseif ($detected['brand'] === null && preg_match('/бренд|производител|brand|vendor/u', $h)) {
                $detected['brand'] = $i;
            } elseif ($detected['qty'] === null && preg_match('/налич|кол|количеств|остаток|склад|stock|qty/u', $h)) {
                $detected['qty'] = $i;
            } elseif ($detected['price'] === null && preg_match('/цена|price|стоимост|usd|руб|₽/u', $h)) {
                $detected['price'] = $i;
                $detected['currency'] = preg_match('/usd/u', $h) ? 'usd' : 'rub';
            }
        }

        return $detected;
    }

    /**
     * Нормализация одной строки прайса в запись товара.
     */
    private function buildImportRow(array $source, array $opts, bool $checkExisting = false): array
    {
        $map = $opts['map'] ?? [];
        $cell = static function (array $row, mixed $idx): string {
            return $idx !== null && isset($row[$idx]) ? trim((string) $row[$idx]) : '';
        };

        $sku      = $cell($source, $map['sku'] ?? null);
        $name     = $cell($source, $map['name'] ?? null);
        $brand    = $cell($source, $map['brand'] ?? null);
        $priceRaw = $cell($source, $map['price'] ?? null);
        $qtyRaw   = $cell($source, $map['qty'] ?? null);

        $title = $this->importTitle($name !== '' ? $name : $sku, (string) ($opts['title_mode'] ?? 'full'));

        $price = null;
        if ($priceRaw !== '') {
            $num = $this->parseNum($priceRaw);
            if ($num > 0) {
                $price = ($opts['currency'] ?? 'rub') === 'usd'
                    ? (int) round($num * (float) ($opts['rate'] ?? 0))
                    : (int) round($num);
            } elseif (!$this->priceIsZero($opts)) {
                $price = 0;
            }
        }

        $stock = 0;
        if ($qtyRaw !== '') {
            $stock = max(0, (int) round($this->parseNum($qtyRaw)));
        }
        $stockState = $stock >= 5 ? 'in' : ($stock >= 1 ? 'low' : 'order');

        $existed = null;
        if ($checkExisting && $sku !== '') {
            $existed = (int) (db()->value('SELECT COUNT(*) FROM products WHERE sku = ?', [$sku]) > 0);
        }

        return [
            'sku'         => $sku,
            'brand'       => $brand,
            'name'        => $name,
            'title'       => $title,
            'qty'         => $stock,
            'stock_state' => $stockState,
            'price'       => $price,
            'existed'     => $existed,
        ];
    }

    private function priceIsZero(array $opts): bool
    {
        return !empty($opts['price_zero_null']); // цена 0 — «по запросу»
    }

    private function parseNum(string $raw): float
    {
        $raw = trim((string) preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $raw) ?? '');
        if ($raw === '') {
            return 0.0;
        }
        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $raw = str_replace(',', '', $raw); // «1,234.56» — запятая тысяч
        } else {
            $raw = str_replace(',', '.', $raw); // «2138,86» — десятичная запятая
        }
        $num = (float) $raw;
        return is_finite($num) ? $num : 0.0;
    }

    private function importTitle(string $name, string $mode): string
    {
        $t = trim($name);
        if ($mode === 'after_comma') {
            $parts = explode(',', $t, 2);
            if (isset($parts[1]) && trim($parts[1]) !== '') {
                $t = trim($parts[1]);
            }
        }
        // Убираем хвостовые группы вида (VENDORCODE)(ARTIKUL)
        $t = (string) preg_replace('/\s*(?:\(\s*[A-Za-z0-9][A-Za-z0-9._\/-]*\s*\))+\s*$/u', '', trim($t));
        $t = (string) preg_replace('/\s{2,}/u', ' ', $t);
        $t = mb_substr(trim($t), 0, 250);
        return $t;
    }

    private function importCategoryLabel(array $rec, array $opts): array
    {
        $mode = $opts['category_mode'] ?? 'name';

        if ($mode === 'single') {
            $id = (int) ($opts['category_id'] ?? 0);
            if ($id <= 0) {
                return ['label' => '— категория не выбрана —'];
            }
            $row = db()->one('SELECT name FROM categories WHERE id = ?', [$id]);
            return ['label' => $row !== null ? (string) $row['name'] : '— категория не выбрана —'];
        }

        $label = $mode === 'brand'
            ? trim((string) ($rec['brand'] ?? ''))
            : $this->importCategoryName((string) ($rec['name'] ?? ''), (string) ($rec['brand'] ?? ''));

        if ($label === '') {
            return ['label' => '— пустая категория —'];
        }
        $existing = db()->one('SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1', [$label]);
        return [
            'label'   => $label,
            'created' => $existing === null,
        ];
    }

    /**
     * Категория из первой фразы наименования до первой запятой.
     */
    private function importCategoryName(string $name, string $fallback): string
    {
        $t = trim($name);
        if ($t !== '') {
            $parts = explode(',', $t, 2);
            $prefix = trim($parts[0]);
            if ($prefix !== '') {
                return mb_substr($prefix, 0, 160);
            }
        }
        return trim($fallback);
    }

    private function executeImport(array $rows, array $opts): array
    {
        $report = [
            'inserted' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        $updateExisting = !empty($opts['update_existing']);
        $setOldPrice    = !empty($opts['set_old_price']);
        $mode           = $opts['category_mode'] ?? 'name';
        $byBrand        = $mode === 'brand';
        $singleMode     = $mode === 'single';
        $parentId       = (int) ($opts['parent_id'] ?? 0);
        $singleCat      = (int) ($opts['category_id'] ?? 0);

        if ($singleMode && $singleCat <= 0) {
            $report['errors'][] = 'Не выбрана категория для импорта всех товаров.';
            return $report;
        }

        $brandCatCache = [];
        $pdo = db()->pdo();

        $pdo->beginTransaction();
        try {
            foreach ($rows as $i => $source) {
                $rowNumber = $i + 2; // строка файла (шапка на 1-й строке)
                $rec = $this->buildImportRow($source, $opts);
                $rec['title'] = normalize_product_title((string) $rec['title'], (string) $rec['sku']);

                if ($rec['sku'] === '') {
                    $report['skipped']++;
                    $this->importError($report, "Строка {$rowNumber}: пустой артикул — пропущено.");
                    continue;
                }

                $categoryLabel = $byBrand
                    ? trim((string) $rec['brand'])
                    : $this->importCategoryName((string) $rec['name'], (string) $rec['brand']);
                $categoryId = $singleMode
                    ? $singleCat
                    : $this->categoryByName($categoryLabel, $parentId, $brandCatCache);

                if ($categoryId === null) {
                    $report['skipped']++;
                    $this->importError($report, "Строка {$rowNumber} ({$rec['sku']}): не определена категория — пропущено.");
                    continue;
                }

                $existing = db()->one(
                    'SELECT id, price, description, subtitle FROM products WHERE sku = ? ORDER BY id LIMIT 1',
                    [$rec['sku']]
                );
                $isNew = $existing === null;

                if (!$isNew && !$updateExisting) {
                    $report['skipped']++;
                    continue;
                }

                $oldPrice = null;
                if (!$isNew && $setOldPrice && $rec['price'] !== null) {
                    $currentPrice = $existing['price'] !== null ? (int) $existing['price'] : null;
                    if ($currentPrice !== null && $rec['price'] < $currentPrice) {
                        $oldPrice = $currentPrice;
                    } elseif ($currentPrice !== null && $rec['price'] > $currentPrice) {
                        $oldPrice = null;
                    }
                }

                try {
                    if ($isNew) {
                        $slug = $this->uniqueImportSlug($rec['title']);
                        db()->exec(
                            "INSERT INTO products
                                (category_id, sku, slug, title, subtitle, description,
                                 meta_title, meta_description, price, old_price, stock, stock_state,
                                 specs, configs, tags, group_label, badge, is_hit, is_new, created_at, updated_at)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())",
                            [
                                $categoryId,
                                $rec['sku'],
                                $slug,
                                $rec['title'],
                                $rec['brand'],                                   // subtitle
                                $rec['name'],                                    // description
                                mb_substr($rec['title'], 0, 200),                // meta_title
                                mb_substr($rec['title'], 0, 300),                // meta_description
                                $rec['price'],
                                $oldPrice,
                                $rec['qty'],
                                $rec['stock_state'],
                                null,                                            // specs
                                null,                                            // configs
                                json_encode(array_values(array_filter([$rec['brand']])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                $rec['brand'],                                   // group_label
                                '',                                              // badge
                                0,                                               // is_hit
                                0,                                               // is_new
                            ]
                        );
                        $report['inserted']++;
                    } else {
                        $description = trim((string) ($existing['description'] ?? ''));
                        $subtitle = trim((string) ($existing['subtitle'] ?? ''));
                        db()->exec(
                            "UPDATE products SET
                                category_id = ?, title = ?, price = ?, old_price = ?, stock = ?, stock_state = ?,
                                tags = ?, group_label = ?, description = ?, subtitle = ?,
                                meta_title = ?, meta_description = ?
                             WHERE id = ?",
                            [
                                $categoryId,
                                $rec['title'],
                                $rec['price'],
                                $oldPrice,
                                $rec['qty'],
                                $rec['stock_state'],
                                json_encode(array_values(array_filter([$rec['brand']])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                $rec['brand'],
                                $description !== '' ? $description : $rec['name'],
                                $subtitle !== '' ? $subtitle : $rec['brand'],
                                mb_substr($rec['title'], 0, 200),
                                mb_substr($rec['title'], 0, 300),
                                (int) $existing['id'],
                            ]
                        );
                        $report['updated']++;
                    }
                } catch (\Throwable $e) {
                    $report['skipped']++;
                    $this->importError($report, "Строка {$rowNumber} ({$rec['sku']}): " . $e->getMessage());
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $report['errors'][] = 'Ошибка импорта: ' . $e->getMessage();
        }

        return $report;
    }

    private function importError(array &$report, string $message): void
    {
        if (count($report['errors']) < 50) {
            $report['errors'][] = $message;
        }
    }

    /**
     * Находит категорию по точному имени или создаёт её нового (по бренду или по фразе из названия).
     */
    private function categoryByName(string $name, int $parentId, array &$cache): ?int
    {
        $key = mb_strtolower(trim($name));
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        if ($name === '') {
            return null;
        }

        $existing = db()->one('SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1', [$name]);
        if ($existing !== null) {
            return $cache[$key] = (int) $existing['id'];
        }

        $base = mb_substr(str_slug($name), 0, 60);
        $base = $base !== '' ? $base : 'brand';
        $slug = $base;
        $i = 2;
        while (db()->value('SELECT COUNT(*) FROM categories WHERE slug = ?', [$slug]) > 0) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 50) {
                $slug = $base . '-' . bin2hex(random_bytes(3));
                break;
            }
        }

        db()->exec(
            'INSERT INTO categories (slug, name, parent_id, short_desc, description, sort_order, is_active)
             VALUES (?,?,?,?,?,?,1)',
            [$slug, $name, $parentId > 0 ? $parentId : null, '', '', 0]
        );
        return $cache[$key] = (int) db()->lastId();
    }

    private function uniqueImportSlug(string $title): string
    {
        $base = mb_substr(str_slug($title) !== '' ? str_slug($title) : 'item', 0, 180);
        $slug = $base;
        $i = 2;
        while (db()->value('SELECT COUNT(*) FROM products WHERE slug = ?', [$slug]) > 0) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 999) {
                $slug = $base . '-' . bin2hex(random_bytes(3));
                break;
            }
        }
        return $slug;
    }

    private function importState(): array
    {
        $state = $_SESSION['adm_import'] ?? [];
        return is_array($state) ? $state : [];
    }

    private function saveImportState(array $state): void
    {
        $_SESSION['adm_import'] = $state;
    }

    private function clearImportFile(): void
    {
        $state = $this->importState();
        $file = (string) ($state['file'] ?? '');
        if ($file !== '' && strpos($file, DIRECTORY_SEPARATOR) === false && strpos($file, '/') === false) {
            $path = $this->importDir() . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        unset($_SESSION['adm_import']);
    }

    private function importDir(): string
    {
        $dir = APP_ROOT . '/data/imports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    // ---------------- Categories ----------------

    private function categoriesIndex(): void
    {
        $tree = $this->categories->tree(false);
        $all = $this->categories->all();

        $this->render('categories', [
            'tree'  => $tree,
            'total' => count($all),
        ]);
    }

    private function categoryCreate(): void
    {
        $this->render('category-form', [
            'category' => null,
            'tree'     => $this->categories->tree(false),
            'errors'   => [],
            'old'      => [],
        ]);
    }

    private function categoryStore(): void
    {
        csrf_check();
        $data = $this->collectCategoryData();
        $errors = $this->validateCategory($data, 0);

        if (!$errors) {
            $slug = $this->uniqueCategorySlug($data['slug'], 0);
            try {
                db()->exec(
                    "INSERT INTO categories (slug, name, title, parent_id, short_desc, meta_title, meta_description, description, sort_order, is_active)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    [$slug, $data['name'], $data['title'], $data['parent_id'], $data['short_desc'], $data['meta_title'], $data['meta_description'], $data['description'], $data['sort_order'], $data['is_active']]
                );
                redirect('/admin/categories?created=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('category-form', [
            'category' => null,
            'tree'     => $this->categories->tree(false),
            'errors'   => $errors,
            'old'      => $data,
        ]);
    }

    private function categoryEdit(int $id): void
    {
        $category = $this->categories->find($id);
        if ($category === null) {
            $this->adminError('Категория не найдена');
        }

        $this->render('category-form', [
            'category' => $category,
            'tree'     => $this->categories->tree(false),
            'errors'   => [],
            'old'      => [],
        ]);
    }

    private function categoryUpdate(int $id): void
    {
        csrf_check();
        $category = $this->categories->find($id);
        if ($category === null) {
            $this->adminError('Категория не найдена');
        }

        $data = $this->collectCategoryData();
        $errors = $this->validateCategory($data, $id);

        if (!$errors) {
            $slug = $this->uniqueCategorySlug($data['slug'], $id);
            try {
                db()->exec(
                    "UPDATE categories SET slug = ?, name = ?, title = ?, parent_id = ?,
                         short_desc = ?, meta_title = ?, meta_description = ?, description = ?, sort_order = ?, is_active = ?
                     WHERE id = ?",
                    [$slug, $data['name'], $data['title'], $data['parent_id'], $data['short_desc'], $data['meta_title'], $data['meta_description'], $data['description'], $data['sort_order'], $data['is_active'], $id]
                );
                redirect('/admin/categories?saved=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('category-form', [
            'category' => $this->categories->find($id),
            'tree'     => $this->categories->tree(false),
            'errors'   => $errors,
            'old'      => $data,
        ]);
    }

    private function categoryDestroy(int $id): void
    {
        csrf_check();
        if ($this->categories->find($id) === null) {
            redirect('/admin/categories');
        }
        if ($this->categories->childrenCount($id) > 0) {
            redirect('/admin/categories?error=children');
        }
        if (db()->value('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]) > 0) {
            redirect('/admin/categories?error=products');
        }
        db()->exec('DELETE FROM categories WHERE id = ?', [$id]);
        redirect('/admin/categories?deleted=1');
    }

    private function categoryToggle(int $id): void
    {
        csrf_check();
        $category = $this->categories->find($id);
        if ($category !== null) {
            db()->exec('UPDATE categories SET is_active = ? WHERE id = ?', [(int) !(int) $category['is_active'], $id]);
        }
        redirect('/admin/categories');
    }

    private function sortCategories(): never
    {
        csrf_check();
        $id = (int) ($_POST['id'] ?? 0);
        $target = (int) ($_POST['target'] ?? 0);
        $place = (string) ($_POST['place'] ?? '');

        if ($id <= 0 || $target <= 0 || $id === $target || !in_array($place, ['before', 'after', 'inside'], true)) {
            json_response(['ok' => false], 422);
        }

        $row = db()->one('SELECT id, parent_id FROM categories WHERE id = ?', [$id]);
        $tar = db()->one('SELECT id, parent_id FROM categories WHERE id = ?', [$target]);
        if ($row === null || $tar === null) {
            json_response(['ok' => false], 404);
        }

        // Новая группа (до/после — рядом с целью; inside — последним ребёнком цели)
        $newParent = $place === 'inside' ? (int) $tar['id'] : ($tar['parent_id'] === null ? null : (int) $tar['parent_id']);
        $oldParent = $row['parent_id'] === null ? null : (int) $row['parent_id'];

        // Защита от циклов: новый родитель не может лежать внутри перетаскиваемого поддерева
        if ($newParent !== null) {
            foreach ($this->categories->descendants($id) as $desc) {
                if ((int) $desc['id'] === $newParent) {
                    json_response(['ok' => false], 422);
                }
            }
        }

        $pdo = db()->pdo();
        $pdo->beginTransaction();
        try {
            $oldRows = $this->categoryGroup($oldParent);
            $newRows = $place === 'inside'
                ? $this->categoryGroup((int) $tar['id'])
                : $this->categoryGroup($newParent);

            $oldRows = $this->withoutRow($oldRows, $id);
            $newRows = $this->withoutRow($newRows, $id);

            if ($place === 'inside') {
                array_push($newRows, ['id' => $id]);
            } else {
                $idx = $this->rowIndex($newRows, $target);
                if ($idx === null) {
                    $idx = count($newRows);
                }
                if ($place === 'after') {
                    $idx++;
                }
                array_splice($newRows, $idx, 0, [['id' => $id]]);
            }

            if ($newParent !== $oldParent) {
                db()->exec('UPDATE categories SET parent_id = ? WHERE id = ?', [$newParent, $id]);
            }

            if ($oldParent !== $newParent) {
                $this->renumberGroup('categories', $oldRows);
            }
            $this->renumberGroup('categories', $newRows);
            $pdo->commit();
        } catch (\Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['ok' => false], 500);
        }
        json_response(['ok' => true]);
    }

    private function categoryGroup(?int $parent): array
    {
        return db()->run('SELECT id FROM categories WHERE (parent_id <=> ?) ORDER BY sort_order, id', [$parent]);
    }

    private function withoutRow(array $rows, int $removeId): array
    {
        return array_values(array_filter($rows, static fn(array $r): bool => (int) $r['id'] !== $removeId));
    }

    private function rowIndex(array $rows, int $id): ?int
    {
        foreach ($rows as $i => $r) {
            if ((int) $r['id'] === $id) {
                return $i;
            }
        }
        return null;
    }

    private function renumberGroup(string $table, array $rows): void
    {
        $stmt = db()->pdo()->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
        foreach ($rows as $i => $r) {
            $stmt->execute([$i + 1, (int) $r['id']]);
        }
    }

    private function collectCategoryData(): array
    {
        $input = static function (string $key, string $default = ''): string {
            return trim((string) ($_POST[$key] ?? $default));
        };

        $parent = (int) $input('parent_id');

        return [
            'name'             => $input('name'),
            'title'            => $input('title'),
            'slug'             => $input('slug'),
            'parent_id'        => $parent > 0 ? $parent : null,
            'short_desc'       => $input('short_desc'),
            'meta_title'       => $input('meta_title'),
            'meta_description' => $input('meta_description'),
            'description'      => trim((string) ($_POST['description'] ?? '')),
            'sort_order'       => is_numeric($input('sort_order', '0')) ? (int) $input('sort_order', '0') : 0,
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validateCategory(array $d, int $id): array
    {
        $errors = [];
        if ($d['name'] === '') {
            $errors['name'] = 'Укажите название категории';
        }
        if (mb_strlen($d['slug']) > 64 || mb_strlen($d['name']) > 160) {
            $errors['name'] = $errors['name'] ?? 'Слишком длинное название или URL';
        }
        if ($d['parent_id'] > 0) {
            if ($d['parent_id'] === $id) {
                $errors['parent_id'] = 'Категория не может быть родителем самой себя';
            } else {
                foreach ($this->categories->descendants($id) as $desc) {
                    if ((int) $desc['id'] === $d['parent_id']) {
                        $errors['parent_id'] = 'Нельзя вложить категорию в собственную подкатегорию';
                        break;
                    }
                }
            }
        }
        if (mb_strlen($d['short_desc']) > 255) {
            $errors['short_desc'] = 'Краткое описание слишком длинное';
        }
        if (mb_strlen($d['title']) > 255) {
            $errors['title'] = 'Название категории слишком длинное';
        }
        if (mb_strlen($d['meta_title']) > 200) {
            $errors['meta_title'] = 'Title слишком длинный';
        }
        if (mb_strlen($d['meta_description']) > 300) {
            $errors['meta_description'] = 'SEO-описание слишком длинное';
        }
        return $errors;
    }

    private function uniqueCategorySlug(string $slug, int $ignoreId): string
    {
        $slug = $slug !== '' ? str_slug($slug) : str_slug((string) ($_POST['name'] ?? ''));
        $base = $slug;
        $i = 2;
        while ($this->categories->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    // ---------------- Menu ----------------

    private function menuPosition(): string
    {
        $pos = (string) ($_GET['position'] ?? 'top');
        return isset(self::MENU_POSITIONS[$pos]) ? $pos : 'top';
    }

    private function menuIndex(): void
    {
        $position = $this->menuPosition();
        $items = $this->menu->all($position);

        $this->render('menu', [
            'position'  => $position,
            'positions' => self::MENU_POSITIONS,
            'items'     => $items,
        ]);
    }

    private function menuCreate(): void
    {
        $position = $this->menuPosition();

        $this->render('menu-form', [
            'item'     => null,
            'position' => $position,
            'positions' => self::MENU_POSITIONS,
            'tree'     => $this->categories->tree(false),
            'errors'   => [],
            'old'      => [],
        ]);
    }

    private function menuStore(): void
    {
        csrf_check();
        $data = $this->collectMenuData();
        $errors = $this->validateMenu($data);

        if (!$errors) {
            try {
                $this->menu->create($data);
                redirect('/admin/menu?position=' . $data['position'] . '&created=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('menu-form', [
            'item'     => null,
            'position' => $data['position'],
            'positions' => self::MENU_POSITIONS,
            'tree'     => $this->categories->tree(false),
            'errors'   => $errors,
            'old'      => $data,
        ]);
    }

    private function menuEdit(int $id): void
    {
        $item = $this->menu->find($id);
        if ($item === null) {
            $this->adminError('Пункт меню не найден');
        }

        $this->render('menu-form', [
            'item'     => $item,
            'position' => (string) ($item['position'] ?? 'top'),
            'positions' => self::MENU_POSITIONS,
            'tree'     => $this->categories->tree(false),
            'errors'   => [],
            'old'      => [],
        ]);
    }

    private function menuUpdate(int $id): void
    {
        csrf_check();
        if ($this->menu->find($id) === null) {
            $this->adminError('Пункт меню не найден');
        }

        $data = $this->collectMenuData();
        $errors = $this->validateMenu($data);

        if (!$errors) {
            try {
                $this->menu->update($id, $data);
                redirect('/admin/menu?position=' . $data['position'] . '&saved=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('menu-form', [
            'item'     => $this->menu->find($id),
            'position' => $data['position'],
            'positions' => self::MENU_POSITIONS,
            'tree'     => $this->categories->tree(false),
            'errors'   => $errors,
            'old'      => $data,
        ]);
    }

    private function menuDestroy(int $id): void
    {
        csrf_check();
        $item = $this->menu->find($id);
        $position = $item !== null ? (string) ($item['position'] ?? 'top') : 'top';
        $this->menu->delete($id);
        redirect('/admin/menu?position=' . $position . '&deleted=1');
    }

    private function menuToggle(int $id): void
    {
        csrf_check();
        $item = $this->menu->find($id);
        if ($item !== null) {
            $position = (string) ($item['position'] ?? 'top');
            $this->menu->update($id, $this->asData($item, (int) !(int) $item['is_active']));
            redirect('/admin/menu?position=' . $position);
        }
        redirect('/admin/menu');
    }

    private function sortMenu(): never
    {
        csrf_check();
        $id = (int) ($_POST['id'] ?? 0);
        $target = (int) ($_POST['target'] ?? 0);
        $place = (string) ($_POST['place'] ?? '');

        if ($id <= 0 || $target <= 0 || $id === $target || !in_array($place, ['before', 'after'], true)) {
            json_response(['ok' => false], 422);
        }

        $row = db()->one('SELECT id, position FROM menu_items WHERE id = ?', [$id]);
        $tar = db()->one('SELECT id, position FROM menu_items WHERE id = ?', [$target]);
        if ($row === null || $tar === null) {
            json_response(['ok' => false], 404);
        }

        $oldPos = (string) $row['position'];
        $newPos = (string) $tar['position'];

        $pdo = db()->pdo();
        $pdo->beginTransaction();
        try {
            $oldRows = db()->run('SELECT id FROM menu_items WHERE position = ? ORDER BY sort_order, id', [$oldPos]);
            $newRows = db()->run('SELECT id FROM menu_items WHERE position = ? ORDER BY sort_order, id', [$newPos]);

            $oldRows = $this->withoutRow($oldRows, $id);
            $newRows = $this->withoutRow($newRows, $id);

            $idx = $this->rowIndex($newRows, $target);
            if ($idx === null) {
                $idx = count($newRows);
            }
            if ($place === 'after') {
                $idx++;
            }
            array_splice($newRows, $idx, 0, [['id' => $id]]);

            if ($oldPos !== $newPos) {
                db()->exec('UPDATE menu_items SET position = ? WHERE id = ?', [$newPos, $id]);
                $this->renumberGroup('menu_items', $oldRows);
            }
            $this->renumberGroup('menu_items', $newRows);
            $pdo->commit();
        } catch (\Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['ok' => false], 500);
        }
        json_response(['ok' => true]);
    }

    private function asData(array $item, int $isActive): array
    {
        $categoryId = (int) ($item['category_id'] ?? 0);

        return [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'label'       => (string) ($item['label'] ?? ''),
            'url'         => (string) ($item['url'] ?? ''),
            'position'    => isset(self::MENU_POSITIONS[$item['position'] ?? '']) ? (string) $item['position'] : 'top',
            'sort_order'  => (int) ($item['sort_order'] ?? 0),
            'is_active'   => $isActive,
        ];
    }

    private function collectMenuData(): array
    {
        $input = static function (string $key, string $default = ''): string {
            return trim((string) ($_POST[$key] ?? $default));
        };

        $categoryId = (int) $input('category_id');
        $position = $input('position', 'top');

        return [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'label'       => $input('label'),
            'url'         => $input('url'),
            'position'    => isset(self::MENU_POSITIONS[$position]) ? $position : 'top',
            'sort_order'  => is_numeric($input('sort_order', '0')) ? (int) $input('sort_order', '0') : 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validateMenu(array $d): array
    {
        $errors = [];
        if (!isset(self::MENU_POSITIONS[$d['position']])) {
            $errors['position'] = 'Неизвестная позиция меню';
        }
        if ($d['category_id'] > 0) {
            if ($this->categories->find($d['category_id']) === null) {
                $errors['category_id'] = 'Категория не найдена';
            }
        } else {
            if ($d['label'] === '') {
                $errors['label'] = 'Укажите название пункта';
            }
            if ($d['url'] === '' || !preg_match('#^/[^/]|^https?://#i', $d['url'])) {
                $errors['url'] = 'Укажите ссылку (например /contacts или https://…)';
            }
        }
        return $errors;
    }

    // ---------------- Pages ----------------

    private function pagesIndex(): void
    {
        $items = $this->pages->all();

        $this->render('pages', [
            'items' => $items,
        ]);
    }

    private function pageCreate(): void
    {
        $this->render('page-form', [
            'pg'     => null,
            'errors' => [],
            'old'    => [],
        ]);
    }

    private function pageStore(): void
    {
        csrf_check();
        $data = $this->collectPageData();
        $errors = $this->validatePage($data, 0);

        if (!$errors) {
            $data['slug'] = $this->uniquePageSlug($data['slug'], 0);
            try {
                $this->pages->create($data);
                redirect('/admin/pages?created=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('page-form', [
            'pg'     => null,
            'errors' => $errors,
            'old'    => $data,
        ]);
    }

    private function pageEdit(int $id): void
    {
        $page = $this->pages->find($id);
        if ($page === null) {
            $this->adminError('Страница не найдена');
        }

        $this->render('page-form', [
            'pg'     => $page,
            'errors' => [],
            'old'    => [],
        ]);
    }

    private function pageUpdate(int $id): void
    {
        csrf_check();
        if ($this->pages->find($id) === null) {
            $this->adminError('Страница не найдена');
        }

        $data = $this->collectPageData();
        $errors = $this->validatePage($data, $id);

        if (!$errors) {
            $data['slug'] = $this->uniquePageSlug($data['slug'], $id);
            try {
                $this->pages->update($id, $data);
                redirect('/admin/pages?saved=1');
            } catch (\Throwable $e) {
                $errors['db'] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }

        $this->render('page-form', [
            'pg'     => $this->pages->find($id),
            'errors' => $errors,
            'old'    => $data,
        ]);
    }

    private function pageDestroy(int $id): void
    {
        csrf_check();
        $this->pages->delete($id);
        redirect('/admin/pages?deleted=1');
    }

    private function pageToggle(int $id): void
    {
        csrf_check();
        $page = $this->pages->find($id);
        if ($page !== null) {
            $this->pages->update($id, [
                'slug'             => $page['slug'],
                'title'            => $page['title'],
                'content'          => $page['content'],
                'meta_title'       => $page['meta_title'],
                'meta_description' => $page['meta_description'],
                'sort_order'       => (int) $page['sort_order'],
                'is_active'        => (int) !(int) $page['is_active'],
            ]);
        }
        redirect('/admin/pages');
    }

    private function collectPageData(): array
    {
        $input = static function (string $key, string $default = ''): string {
            return trim((string) ($_POST[$key] ?? $default));
        };

        return [
            'slug'             => $input('slug'),
            'title'            => $input('title'),
            'content'          => (string) ($_POST['content'] ?? ''),
            'meta_title'       => $input('meta_title'),
            'meta_description' => $input('meta_description'),
            'sort_order'       => is_numeric($input('sort_order', '0')) ? (int) $input('sort_order', '0') : 0,
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validatePage(array $d, int $id): array
    {
        $errors = [];
        if ($d['title'] === '') {
            $errors['title'] = 'Укажите название страницы';
        }
        $slug = $d['slug'] !== '' ? str_slug($d['slug']) : str_slug($d['title']);
        if (in_array($slug, ['catalog', 'product', 'admin', 'api', 'search', 'cart', 'order', 'order-success', 'sitemap.xml'], true)) {
            $errors['slug'] = 'Этот URL занят системой';
        }
        if ($this->categories->bySlug($slug) !== null) {
            $errors['slug'] = $errors['slug'] ?? 'URL совпадает с категорией';
        }
        return $errors;
    }

    private function uniquePageSlug(string $slug, int $ignoreId): string
    {
        $slug = $slug !== '' ? str_slug($slug) : str_slug((string) ($_POST['title'] ?? ''));
        $base = $slug;
        $i = 2;
        while ($this->pages->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    // ---------------- Gallery ----------------

    private function gallery(int $id): void
    {
        $product = $this->products->find($id);
        if ($product === null) {
            $this->adminError('Товар не найден');
        }
        $images = $this->imagesFor((int) $product['id']);

        $this->render('gallery', [
            'product' => $product,
            'images'  => $images,
            'error'   => $_GET['error'] ?? null,
        ]);
    }

    private function setMainImage(int $id, int $imageId): void
    {
        csrf_check();
        $img = $this->imageById($imageId);
        if ($img !== null && (int) $img['product_id'] === $id) {
            $old = (string) ($this->products->find($id)['image'] ?? '');
            db()->exec('UPDATE products SET image = ?, updated_at = NOW() WHERE id = ?', [$img['filename'], $id]);
            if ($old !== '' && $old !== $img['filename'] && !$this->isInGallery($id, $old)) {
                $this->deleteUploadedFile($old);
            }
        }
    }

    private function deleteImage(int $id, int $imageId): void
    {
        csrf_check();
        $img = $this->imageById($imageId);
        if ($img === null || (int) $img['product_id'] !== $id) {
            return;
        }
        db()->exec('DELETE FROM product_images WHERE id = ?', [$imageId]);
        $this->deleteUploadedFile((string) $img['filename']);
        if (($this->products->find($id)['image'] ?? '') === $img['filename']) {
            db()->exec('UPDATE products SET image = \'\' WHERE id = ?', [$id]);
        }
    }

    private function uploadImages(int $id): void
    {
        csrf_check();
        $files = $_FILES['images'] ?? null;
        if (!is_array($files) || !is_array($files['name'] ?? null)) {
            redirect('/admin/products/' . $id . '/gallery?error=1');
        }

        $uploaded = 0;
        $errors = 0;
        $dir = $this->uploadDir();

        for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
            if (($files['error'][$i] ?? 1) !== UPLOAD_ERR_OK || empty($files['name'][$i])) {
                continue;
            }
            $filename = $this->storeUploadedFile([
                'tmp_name' => $files['tmp_name'][$i],
                'name'     => $files['name'][$i],
                'size'     => (int) $files['size'][$i],
            ], $dir);
            if ($filename === null) {
                $errors++;
                continue;
            }
            $maxSort = (int) db()->value(
                'SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?',
                [$id]
            );
            db()->exec(
                'INSERT INTO product_images (product_id, filename, sort_order) VALUES (?,?,?)',
                [$id, $filename, $maxSort + 1]
            );
            $uploaded++;
        }

        $q = '';
        if ($errors > 0) {
            $q = '?error=1';
        }
        redirect('/admin/products/' . $id . '/gallery' . $q);
    }

    // ---------------- Images ----------------

    private function saveMainImage(int $id, ?array $file): void
    {
        if ($file === null || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return;
        }
        $dir = $this->uploadDir();
        $filename = $this->storeUploadedFile($file, $dir);
        if ($filename === null) {
            return;
        }
        $old = (string) ($this->products->find($id)['image'] ?? '');
        db()->exec("UPDATE products SET image = ?, updated_at = NOW() WHERE id = ?", [$filename, $id]);
        if ($old !== '' && $old !== $filename && !$this->isInGallery($id, $old)) {
            $this->deleteUploadedFile($old);
        }
    }

    private function isInGallery(int $productId, string $filename): bool
    {
        $row = db()->one(
            'SELECT id FROM product_images WHERE product_id = ? AND filename = ?',
            [$productId, $filename]
        );
        return $row !== null;
    }

    /**
     * Валидация и сохранение одного файла. Возвращает имя файла или null.
     */
    private function storeUploadedFile(array $file, string $dir): ?string
    {
        $tmp = $file['tmp_name'] ?? $file['tmp'] ?? '';
        if ($tmp === '') {
            return null;
        }
        if ($file['size'] > 8 * 1024 * 1024) {
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return null;
        }
        $info = @getimagesize((string) $tmp);
        if ($info === false) {
            return null;
        }
        $filename = bin2hex(random_bytes(12)) . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!@move_uploaded_file((string) $tmp, $target)) {
            return null;
        }
        return $filename;
    }

    private function uploadDir(): string
    {
        $dir = APP_ROOT . '/public/uploads/products';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function deleteUploadedFile(string $filename): void
    {
        $path = $this->uploadDir() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path) && strpos($filename, DIRECTORY_SEPARATOR) === false) {
            @unlink($path);
        }
    }

    private function imagesFor(int $productId): array
    {
        return db()->run(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id',
            [$productId]
        );
    }

    private function imageById(int $id): ?array
    {
        $row = db()->one('SELECT * FROM product_images WHERE id = ?', [$id]);
        return $row ?: null;
    }

    // ---------------- Data helpers ----------------

    private function collectData(): array
    {
        $input = static function (string $key, string $default = ''): string {
            return trim((string) ($_POST[$key] ?? $default));
        };

        $specsRaw = $input('specs');
        $configsRaw = $input('configs');
        $specs = $specsRaw !== '' ? decode_json($specsRaw) : [];
        $configs = $configsRaw !== '' ? decode_json($configsRaw) : [];

        $tags = [];
        foreach (explode(',', $input('tags')) as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return [
            'category_id'    => (int) $input('category_id'),
            'sku'            => $input('sku'),
            'slug'           => $input('slug'),
            'title'          => $input('title'),
            'subtitle'       => $input('subtitle'),
            'description'    => trim((string) ($_POST['description'] ?? '')),
            'meta_title'     => $input('meta_title'),
            'meta_description' => $input('meta_description'),
            'price'          => $input('price') !== '' && is_numeric($input('price')) ? (int) $input('price') : null,
            'old_price'    => $input('old_price') !== '' && is_numeric($input('old_price')) ? (int) $input('old_price') : null,
            'stock'        => is_numeric($input('stock', '0')) ? (int) $input('stock', '0') : 0,
            'stock_state'  => in_array($input('stock_state'), ['in', 'low', 'out', 'order'], true) ? $input('stock_state') : 'in',
            'specs_json'   => $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'configs_json' => $configs ? json_encode($configs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'tags_json'    => $tags ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null,
            'group_label'  => $input('group_label'),
            'badge'        => $input('badge'),
            'is_hit'       => isset($_POST['is_hit']) ? 1 : 0,
            'is_new'       => isset($_POST['is_new']) ? 1 : 0,
            'main_image'   => $_FILES['main_image'] ?? null,
        ];
    }

    private function validate(array $d): array
    {
        $errors = [];
        if ($d['title'] === '') {
            $errors['title'] = 'Укажите название товара';
        }
        if ($d['sku'] === '') {
            $errors['sku'] = 'Укажите артикул';
        }
        if ($d['category_id'] <= 0) {
            $errors['category_id'] = 'Выберите категорию';
        }
        if (mb_strlen($d['description']) > 20000) {
            $errors['description'] = 'Описание слишком длинное';
        }
        if (mb_strlen($d['meta_title']) > 200) {
            $errors['meta_title'] = 'Title слишком длинный';
        }
        if (mb_strlen($d['meta_description']) > 300) {
            $errors['meta_description'] = 'SEO-описание слишком длинное';
        }
        return $errors;
    }

    /**
     * Слаги должны быть уникальны. Пустой слаг генерируется из названия.
     */
    private function uniqueSlug(string $slug, int $ignoreId): string
    {
        $slug = $slug !== '' ? str_slug($slug) : str_slug((string) ($_POST['title'] ?? ''));
        $base = $slug;
        $i = 2;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugExists(string $slug, int $ignoreId): bool
    {
        $row = db()->one('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $ignoreId]);
        return $row !== null;
    }

    // ---------------- Render ----------------

    private function render(string $template, array $data = []): void
    {
        view('admin/' . $template, array_merge($data, [
            'categories' => $this->categories->tree(),
            'page'       => ['admin' => true, 'title' => 'Администрирование'],
        ]));
    }

    private function adminError(string $message): never
    {
        http_response_code(404);
        $this->render('error', ['message' => $message]);
        exit;
    }
}