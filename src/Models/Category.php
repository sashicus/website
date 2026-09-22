<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Category
{
    private Database $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function allActive(): array
    {
        return $this->all(true);
    }

    public function all(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE c.is_active = 1' : '';
        return $this->db->run(
            "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c
             $where
             ORDER BY c.sort_order, c.id"
        );
    }

    public function tree(bool $activeOnly = true): array
    {
        $categories = $this->all($activeOnly);
        $tree = [];
        foreach ($categories as $cat) {
            if ($cat['parent_id'] === null) {
                $cat['children'] = [];
                $tree[$cat['id']] = $cat;
            }
        }
        foreach ($categories as $cat) {
            if ($cat['parent_id'] !== null && isset($tree[$cat['parent_id']])) {
                $tree[$cat['parent_id']]['children'][] = $cat;
            }
        }
        return array_values($tree);
    }

    public function find(int $id): ?array
    {
        return $this->db->one(
            "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c
             WHERE c.id = ?",
            [$id]
        );
    }

    public function slugExists(string $slug, int $ignoreId): bool
    {
        return $this->db->one('SELECT id FROM categories WHERE slug = ? AND id <> ?', [$slug, $ignoreId]) !== null;
    }

    public function childrenCount(int $id): int
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM categories WHERE parent_id = ?', [$id]);
    }

    public function bySlug(string $slug): ?array
    {
        return $this->db->one(
            "SELECT * FROM categories WHERE slug = ? AND is_active = 1",
            [$slug]
        );
    }

    /**
     * Category + its ancestors, ordered root → this category.
     */
    public function chain(int $categoryId): array
    {
        $rows = $this->db->run(
            "WITH RECURSIVE tree AS (
                 SELECT id, parent_id, name, slug FROM categories WHERE id = ?
                 UNION ALL
                 SELECT c.id, c.parent_id, c.name, c.slug FROM categories c
                 JOIN tree t ON c.id = t.parent_id
             )
             SELECT id, parent_id, name, slug FROM tree",
            [$categoryId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $row;
        }
        $chain = [];
        $current = $map[(int) $categoryId] ?? null;
        while ($current !== null) {
            $chain[] = $current;
            $current = $map[(int) ($current['parent_id'] ?? 0)] ?? null;
        }
        return array_reverse($chain);
    }

    public function children(int $id): array
    {
        return $this->db->run(
            "SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order, id",
            [$id]
        );
    }

    public function descendants(int $id): array
    {
        return $this->db->run(
            "WITH RECURSIVE tree AS (
                 SELECT id, parent_id FROM categories WHERE id = ?
                 UNION ALL
                 SELECT c.id, c.parent_id FROM categories c
                 JOIN tree t ON c.parent_id = t.id
             )
             SELECT id FROM tree",
            [$id]
        );
    }

    public function idList(int $id): array
    {
        $rows = $this->descendants($id);
        return array_map(static fn(array $r): int => (int) $r['id'], $rows);
    }

    /**
     * Category products + products from all descendants.
     */
    public function productIds(int $id): array
    {
        $ids = $this->idList($id);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->run(
            "SELECT DISTINCT p.id FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.category_id IN ($placeholders)",
            $ids
        );
        return array_map(static fn(array $r): int => (int) $r['id'], $rows);
    }
}