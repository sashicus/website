<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\ShopController;
use App\Core\Router;

$router = new Router(static function (string $page, array $segments): void {
    $controller = new ShopController();

    switch ($page) {
        case 'home':
            $controller->home();
            return;

        case 'about':
            $controller->about();
            return;

        case 'contacts':
            $controller->contacts();
            return;

        case 'cart':
            $controller->cart();
            return;

        case 'order':
            $controller->apiOrder();
            return;

        case 'order-success':
            $controller->orderSuccess();
            return;

        case 'search':
            $controller->catalog();
            return;

        case 'catalog':
            // Страница /catalog удалена. Старый URL /catalog/{slug} → 301 на /{slug}
            if (isset($segments[0])) {
                $controller->catalogRedirect($segments[0]);
                return;
            }
            $controller->notFound();
            return;

        case 'product':
            // Старый URL /product/{slug} → 301 на /{category}/{slug}
            if (!isset($segments[0])) {
                $controller->notFound();
                return;
            }
            $controller->productRedirect($segments[0]);
            return;

        case 'sitemap.xml':
            $controller->sitemap();
            return;

        case 'api':
            $sub = $segments[0] ?? '';
            switch ($sub) {
                case 'search':
                case 'suggest':
                    $controller->apiSearch();
                    return;
                case 'cart':
                    $controller->apiCart();
                    return;
                case 'order':
                    $controller->apiOrder();
                    return;
                default:
                    json_response(['ok' => false]);
            }
            return;

        case 'admin':
            $admin = new AdminController();
            $admin->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $segments);
            return;

        default:
            // Динамические URL: /{category}, /{page}, /{category}/{product}
            if (!$segments) {
                $controller->catalogOrPage($page);
            } else {
                $controller->product($page, $segments[0]);
            }
    }
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', Router::path(), $_GET);