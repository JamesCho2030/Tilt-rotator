<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Supplier model managing vendor data and scores.
 */
class Supplier
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO suppliers (supplier_code, supplier_name, business_number, representative, phone, fax, email, address, contact_person, contact_phone, contact_email, payment_terms, payment_days, rating, is_active) VALUES (:supplier_code, :supplier_name, :business_number, :representative, :phone, :fax, :email, :address, :contact_person, :contact_phone, :contact_email, :payment_terms, :payment_days, :rating, :is_active)');
        $stmt->execute([
            'supplier_code' => $data['supplier_code'],
            'supplier_name' => $data['supplier_name'],
            'business_number' => $data['business_number'],
            'representative' => $data['representative'] ?? null,
            'phone' => $data['phone'] ?? null,
            'fax' => $data['fax'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'payment_days' => $data['payment_days'] ?? 30,
            'rating' => $data['rating'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['supplier_code', 'supplier_name', 'business_number', 'representative', 'phone', 'fax', 'email', 'address', 'contact_person', 'contact_phone', 'contact_email', 'payment_terms', 'payment_days', 'rating', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE suppliers SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
