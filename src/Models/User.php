<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * User model handling authentication and authorization data access.
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (email, password, name, department, position, role, approval_limit, is_active) VALUES (:email, :password, :name, :department, :position, :role, :approval_limit, :is_active)');
        $stmt->execute([
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'name' => $data['name'],
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'role' => $data['role'],
            'approval_limit' => $data['approval_limit'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['email', 'name', 'department', 'position', 'role', 'approval_limit', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
