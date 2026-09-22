<?php

declare(strict_types=1);

namespace App\Models;

final class Order
{
    public function create(array $data): int
    {
        $json = json_encode($data['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        db()->exec(
            "INSERT INTO orders (name, phone, email, company, comment, items, total, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'new')",
            [
                $data['name'],
                $data['phone'],
                $data['email'],
                $data['company'],
                $data['comment'],
                $json,
                (int) $data['total'],
            ]
        );
        return db()->lastId();
    }
}