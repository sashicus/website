<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Product
{
    private const ORDER_MAP = [
        'price_asc'  => 'p.price ASC, p.id',
        'price_desc' => 'p.price DESC, p.id',
        'name'       => 'p.title ASC',
        'new'        => 'p.created_at DESC, p.id DESC',
    ];

    public static function hydr(array $row): array
    {
        $row['specs']   = decode_json($row['specs'] ?? null);
        $row['configs'] = decode_json($row['configs'] ?? null);
        $row['tags']    = decode_json($row['tags'] ?? null);
        return $row;
    }

    public function countAll(): int
    {
        return (int) db()->value("SELECT COUNT(*) FROM products");
    }

    public function find(int $id): ?array
    {
        $row = db()->one("SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                          c.parent_id AS category_parent
                          FROM products p JOIN categories c ON c.id = p.category_id
                          WHERE p.id = ?", [$id]);
        return $row ? self::hydr($row) : null;
    }

    public function bySlug(string $slug): ?array
    {
        $row = db()->one("SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                          c.parent_id AS category_parent
                          FROM products p JOIN categories c ON c.id = p.category_id
                          WHERE p.slug = ?", [$slug]);
        return $row ? self::hydr($row) : null;
    }

    public function hits(int $limit = 8): array
    {
        $rows = db()->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.is_hit = 1
             ORDER BY p.id
             LIMIT " . (int) $limit
        );
        return array_map([self::class, 'hydr'], $rows);
    }

    public function newArrivals(int $limit = 8): array
    {
        $rows = db()->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.is_new = 1 OR p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY p.created_at DESC
             LIMIT " . (int) $limit
        );
        return array_map([self::class, 'hydr'], $rows);
    }

    public function byCategory(int $categoryId, string $order = 'new', int $limit = 12): array
    {
        $sort = self::ORDER_MAP[$order] ?? self::ORDER_MAP['new'];
        $rows = db()->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.category_id = ?
             ORDER BY $sort
             LIMIT " . (int) $limit,
            [$categoryId]
        );
        return array_map([self::class, 'hydr'], $rows);
    }

    public function inCategories(array $categoryIds, int $limit = 4): array
    {
        $ids = array_map('intval', $categoryIds);
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.category_id IN ($placeholders)
             ORDER BY p.is_hit DESC, p.created_at DESC, p.id DESC
             LIMIT " . (int) $limit,
            $ids
        );
        return array_map([self::class, 'hydr'], $rows);
    }

    public function related(array $product, int $limit = 4): array
    {
        $tags = array_values(array_filter(array_slice((array) ($product['tags'] ?? []), 0, 3)));
        for ($i = count($tags); $i < 3; $i++) {
            $tags[] = '';
        }
        $t0 = $tags[0] !== '' ? '%' . $tags[0] . '%' : '';
        $t1 = $tags[1] !== '' ? '%' . $tags[1] . '%' : '';
        $t2 = $tags[2] !== '' ? '%' . $tags[2] . '%' : '';

        $params = array_merge(
            [(int) $product['category_id']],
            [$t0, $t0, $t1, $t1, $t2, $t2],
            [(int) $product['id']]
        );

        $rows = db()->run(
            "WITH RECURSIVE up AS (
                 SELECT id, parent_id FROM categories WHERE id = ?
                 UNION ALL
                 SELECT c.id, c.parent_id FROM categories c JOIN up u ON c.id = u.parent_id
             ),
             scope AS (
                 SELECT id FROM up WHERE parent_id IS NULL
                 UNION ALL
                 SELECT c.id FROM categories c JOIN scope s ON c.parent_id = s.id
             ),
             scored AS (
                 SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                        (CASE WHEN ? <> '' THEN (p.tags LIKE ?) ELSE 0 END
                         + CASE WHEN ? <> '' THEN (p.tags LIKE ?) ELSE 0 END
                         + CASE WHEN ? <> '' THEN (p.tags LIKE ?) ELSE 0 END) AS tag_score
                 FROM products p
                 JOIN categories c ON c.id = p.category_id
                 WHERE p.category_id IN (SELECT id FROM scope)
             )
             SELECT * FROM scored
             WHERE id <> ?
             ORDER BY tag_score DESC, is_hit DESC, id
             LIMIT " . (int) $limit,
            $params
        );
        return array_map([self::class, 'hydr'], $rows);
    }

    /**
     * Фотографии галереи товара (дополнительные к главной).
     */
    public function gallery(int $productId): array
    {
        return db()->run(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id",
            [$productId]
        );
    }

    /**
     * Catalog query with filters. Returns [items, total].
     */
    public function search(array $f): array
    {
        $db = db();
        $where = ['1=1'];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = "CONCAT_WS(' ', p.title, p.subtitle, p.sku, p.description) LIKE :q";
            $params['q'] = '%' . $f['q'] . '%';
        }

        if (!empty($f['category_ids'])) {
            $ids = array_map('intval', (array) $f['category_ids']);
            $where[] = 'p.category_id IN (' . implode(',', $ids) . ')';
        }

        if (!empty($f['price_min'])) {
            $where[] = 'p.price >= ' . (int) $f['price_min'];
        }
        if (!empty($f['price_max'])) {
            $where[] = 'p.price <= ' . (int) $f['price_max'];
        }

        if (($f['in_stock'] ?? false) && empty($f['only_order'])) {
            $where[] = "p.stock_state IN ('in','low')";
        }
        if (!empty($f['only_order'])) {
            $where[] = "p.stock_state = 'order'";
        }

        $whereSql = implode(' AND ', $where);
        $sort = self::ORDER_MAP[$f['sort'] ?? 'new'] ?? self::ORDER_MAP['new'];

        $total = (int) $db->value("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);

        $limit = max(1, min(60, (int) ($f['per_page'] ?? 12)));
        $page = max(1, (int) ($f['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $rows = $db->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images_count
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE $whereSql
             ORDER BY $sort
             LIMIT $limit OFFSET $offset",
            $params
        );

        return [array_map([self::class, 'hydr'], $rows), $total, $page, $limit];
    }

    public function countByCategory(): array
    {
        return db()->run(
            "SELECT category_id, COUNT(*) AS n FROM products GROUP BY category_id"
        );
    }

    public function minMaxPrices(): array
    {
        $row = db()->one("SELECT MIN(price) AS mn, MAX(price) AS mx FROM products WHERE price IS NOT NULL");
        return [(int) ($row['mn'] ?? 0), (int) ($row['mx'] ?? 0)];
    }

    public function popular(array $prefixes, int $limit = 8): array
    {
        $params = [];
        $conds = [];
        foreach (array_values($prefixes) as $i => $pfx) {
            $key = ':pfx' . $i;
            $conds[] = 'p.sku LIKE ' . $key;
            $params[$key] = $pfx . '%';
        }
        $condSql = $conds ? '(' . implode(' OR ', $conds) . ')' : '1=1';
        $rows = db()->run(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE $condSql
             ORDER BY p.id
             LIMIT " . (int) $limit,
            $params
        );
        return array_map([self::class, 'hydr'], $rows);
    }
}