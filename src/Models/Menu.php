<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Menu
{
    private Database $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function all(?string $position = null): array
    {
        $sql = "SELECT m.*, c.slug AS category_slug, c.name AS category_name, c.is_active AS category_active
                FROM menu_items m
                LEFT JOIN categories c ON c.id = m.category_id";
        $params = [];
        if ($position !== null && $position !== '') {
            $sql .= " WHERE m.position = ?";
            $params[] = $position;
        }
        $sql .= " ORDER BY m.sort_order, m.id";

        return $this->db->run($sql, $params);
    }

    /**
     * Активные пункты конкретного меню для показа на сайте. Если пункт ссылается
     * на категорию, которая скрыта, он тоже скрывается.
     */
    public function active(string $position): array
    {
        return $this->db->run(
            "SELECT m.*, c.slug AS category_slug, c.name AS category_name
             FROM menu_items m
             LEFT JOIN categories c ON c.id = m.category_id
             WHERE m.position = ? AND m.is_active = 1 AND (m.category_id IS NULL OR c.is_active = 1)
             ORDER BY m.sort_order, m.id",
            [$position]
        );
    }

    public function find(int $id): ?array
    {
        $row = $this->db->one(
            "SELECT m.*, c.slug AS category_slug, c.name AS category_name
             FROM menu_items m
             LEFT JOIN categories c ON c.id = m.category_id
             WHERE m.id = ?",
            [$id]
        );
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->exec(
            "INSERT INTO menu_items (category_id, label, url, position, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['category_id'],
                $data['label'],
                $data['url'],
                $data['position'],
                $data['sort_order'],
                $data['is_active'],
            ]
        );
        return (int) $this->db->lastId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->exec(
            "UPDATE menu_items SET category_id = ?, label = ?, url = ?, position = ?, sort_order = ?, is_active = ?
             WHERE id = ?",
            [
                $data['category_id'],
                $data['label'],
                $data['url'],
                $data['position'],
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->exec('DELETE FROM menu_items WHERE id = ?', [$id]);
    }
}