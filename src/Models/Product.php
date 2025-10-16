<?php
namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Product model responsible for CRUD operations and supplier relations.
 */
class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function paginate(int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE deleted_at IS NULL';

        if ($search) {
            $where .= ' AND (product_name LIKE :search OR product_code LIKE :search)';
            $params['search'] = "%$search%";
        }

        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM products $where ORDER BY id DESC LIMIT :offset, :limit");
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        $items = $stmt->fetchAll();

        $total = (int) $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO products (product_code, product_name, specification, unit, category, safety_stock, reorder_point, image_url, is_active, created_by) VALUES (:product_code, :product_name, :specification, :unit, :category, :safety_stock, :reorder_point, :image_url, :is_active, :created_by)');
        $stmt->execute([
            'product_code' => $data['product_code'],
            'product_name' => $data['product_name'],
            'specification' => $data['specification'] ?? null,
            'unit' => $data['unit'],
            'category' => $data['category'] ?? null,
            'safety_stock' => $data['safety_stock'] ?? 0,
            'reorder_point' => $data['reorder_point'] ?? 0,
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['product_code', 'product_name', 'specification', 'unit', 'category', 'safety_stock', 'reorder_point', 'image_url', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET deleted_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function suppliers(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT ps.*, s.supplier_name FROM product_suppliers ps JOIN suppliers s ON ps.supplier_id = s.id WHERE ps.product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }
}
