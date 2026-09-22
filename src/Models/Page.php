<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Page
{
    private Database $db;

    public function __construct()
    {
        $this->db = db();
    }

    public function all(): array
    {
        return $this->db->run(
            "SELECT * FROM pages ORDER BY sort_order, id"
        );
    }

    public function activePages(): array
    {
        return $this->db->run(
            "SELECT * FROM pages WHERE is_active = 1 ORDER BY sort_order, id"
        );
    }

    public function bySlug(string $slug): ?array
    {
        $row = $this->db->one("SELECT * FROM pages WHERE slug = ?", [$slug]);
        return $row ?: null;
    }

    public function bySlugActive(string $slug): ?array
    {
        $row = $this->db->one(
            "SELECT * FROM pages WHERE slug = ? AND is_active = 1",
            [$slug]
        );
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $row = $this->db->one("SELECT * FROM pages WHERE id = ?", [$id]);
        return $row ?: null;
    }

    public function slugExists(string $slug, int $ignoreId): bool
    {
        return $this->db->one('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $ignoreId]) !== null;
    }

    public function create(array $data): int
    {
        $this->db->exec(
            "INSERT INTO pages (slug, title, content, meta_title, meta_description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['slug'],
                $data['title'],
                $data['content'],
                $data['meta_title'],
                $data['meta_description'],
                $data['sort_order'],
                $data['is_active'],
            ]
        );
        return (int) $this->db->lastId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->exec(
            "UPDATE pages SET slug = ?, title = ?, content = ?, meta_title = ?, meta_description = ?,
                 sort_order = ?, is_active = ? WHERE id = ?",
            [
                $data['slug'],
                $data['title'],
                $data['content'],
                $data['meta_title'],
                $data['meta_description'],
                $data['sort_order'],
                $data['is_active'],
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->exec('DELETE FROM pages WHERE id = ?', [$id]);
    }
}