<?php
namespace App\Models;

use App\Core\Database;
use DateTime;
use PDO;
use RuntimeException;

/**
 * PurchaseOrder model encapsulating business rules and workflow persistence.
 */
class PurchaseOrder
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function generatePoNumber(): string
    {
        $stmt = $this->db->query('CALL sp_generate_po_number(@po_num)');
        $stmt->closeCursor();
        $result = $this->db->query('SELECT @po_num AS po_num')->fetch();
        return $result['po_num'] ?? 'PO-' . date('Ymd') . '-0001';
    }

    public function create(array $data, array $items): int
    {
        $this->db->beginTransaction();

        try {
            if ($data['total_amount'] < 10000) {
                throw new RuntimeException('BR-01: 최소 발주 금액은 10,000원 이상이어야 합니다.');
            }

            $orderDate = new DateTime($data['order_date']);
            $requestedDate = new DateTime($data['requested_delivery_date']);
            $diff = $orderDate->diff($requestedDate)->days;
            if ($requestedDate <= $orderDate || $diff < 3) {
                throw new RuntimeException('BR-02: 납품요청일은 발주일로부터 최소 3영업일 이후여야 합니다.');
            }

            $stmt = $this->db->prepare('INSERT INTO purchase_orders (po_number, supplier_id, order_date, requested_delivery_date, status, total_amount, vat_amount, vat_included, notes, created_by) VALUES (:po_number, :supplier_id, :order_date, :requested_delivery_date, :status, :total_amount, :vat_amount, :vat_included, :notes, :created_by)');
            $stmt->execute([
                'po_number' => $data['po_number'],
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'requested_delivery_date' => $data['requested_delivery_date'],
                'status' => 'PENDING_APPROVAL',
                'total_amount' => $data['total_amount'],
                'vat_amount' => $data['vat_amount'] ?? 0,
                'vat_included' => $data['vat_included'] ?? 1,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            $poId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare('INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price, amount, notes) VALUES (:purchase_order_id, :product_id, :quantity, :unit_price, :amount, :notes)');
            foreach ($items as $item) {
                $itemStmt->execute([
                    'purchase_order_id' => $poId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->initializeApprovals($poId, $data['total_amount']);

            $this->db->commit();
            return $poId;
        } catch (RuntimeException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function initializeApprovals(int $poId, float $totalAmount): void
    {
        $levels = 1;
        if ($totalAmount >= 5000000) {
            $levels = 3;
        } elseif ($totalAmount >= 1000000) {
            $levels = 2;
        }

        $users = $this->db->prepare('SELECT id, approval_limit FROM users WHERE role = "APPROVER" AND is_active = 1 ORDER BY approval_limit');
        $users->execute();
        $approvers = $users->fetchAll();

        $level = 1;
        foreach ($approvers as $approver) {
            if ($level > $levels) {
                break;
            }
            $stmt = $this->db->prepare('INSERT INTO approvals (purchase_order_id, approver_id, approval_level, status) VALUES (:purchase_order_id, :approver_id, :approval_level, :status)');
            $stmt->execute([
                'purchase_order_id' => $poId,
                'approver_id' => $approver['id'],
                'approval_level' => $level,
                'status' => 'PENDING',
            ]);
            $level++;
        }
    }

    public function addApprovalDecision(int $approvalId, string $status, ?string $comments): bool
    {
        $stmt = $this->db->prepare('UPDATE approvals SET status = :status, comments = :comments, approved_at = CURRENT_TIMESTAMP WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'comments' => $comments,
            'id' => $approvalId,
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM purchase_orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $po = $stmt->fetch();
        if (!$po) {
            return null;
        }

        $itemsStmt = $this->db->prepare('SELECT i.*, p.product_name, p.unit FROM purchase_order_items i JOIN products p ON i.product_id = p.id WHERE i.purchase_order_id = :id');
        $itemsStmt->execute(['id' => $id]);
        $po['items'] = $itemsStmt->fetchAll();

        $approvalsStmt = $this->db->prepare('SELECT a.*, u.name, u.position FROM approvals a JOIN users u ON a.approver_id = u.id WHERE purchase_order_id = :id ORDER BY approval_level');
        $approvalsStmt->execute(['id' => $id]);
        $po['approvals'] = $approvalsStmt->fetchAll();

        return $po;
    }
}
