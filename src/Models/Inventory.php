<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Inventory model to manage stock levels and transactions.
 */
class Inventory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getProductInventory(int $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM inventory WHERE product_id = :product_id');
        $stmt->execute(['product_id' => $productId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function adjustStock(array $data): bool
    {
        if ($data['transaction_type'] === 'OUT') {
            $current = $this->getProductInventory((int) $data['product_id']);
            if ($current && $current['quantity'] < $data['quantity']) {
                throw new RuntimeException('BR-05: 출고 수량은 현재 재고 이하이어야 합니다.');
            }
        }

        $stmt = $this->db->prepare('INSERT INTO inventory_transactions (product_id, transaction_type, quantity, warehouse_location, reference_type, reference_id, notes, created_by) VALUES (:product_id, :transaction_type, :quantity, :warehouse_location, :reference_type, :reference_id, :notes, :created_by)');
        return $stmt->execute([
            'product_id' => $data['product_id'],
            'transaction_type' => $data['transaction_type'],
            'quantity' => $data['quantity'],
            'warehouse_location' => $data['warehouse_location'] ?? 'MAIN',
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'],
        ]);
    }

    public function receiveGoods(int $poItemId, float $quantity, int $userId): void
    {
        $stmt = $this->db->prepare('SELECT poi.*, po.supplier_id, p.product_name FROM purchase_order_items poi JOIN purchase_orders po ON poi.purchase_order_id = po.id JOIN products p ON poi.product_id = p.id WHERE poi.id = :id');
        $stmt->execute(['id' => $poItemId]);
        $item = $stmt->fetch();
        if (!$item) {
            throw new RuntimeException('입고 대상 품목을 찾을 수 없습니다.');
        }

        if ($quantity + $item['received_quantity'] > $item['quantity']) {
            throw new RuntimeException('BR-04: 입고 수량은 발주 수량 이하여야 합니다.');
        }

        $updateStmt = $this->db->prepare('UPDATE purchase_order_items SET received_quantity = received_quantity + :quantity WHERE id = :id');
        $updateStmt->execute(['quantity' => $quantity, 'id' => $poItemId]);

        $this->adjustStock([
            'product_id' => $item['product_id'],
            'transaction_type' => 'IN',
            'quantity' => $quantity,
            'warehouse_location' => 'MAIN',
            'reference_type' => 'PURCHASE_ORDER',
            'reference_id' => $item['purchase_order_id'],
            'notes' => '발주 입고 처리',
            'created_by' => $userId,
        ]);
    }
}
