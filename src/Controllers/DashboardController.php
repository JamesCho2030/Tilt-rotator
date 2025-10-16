<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use PDO;

/**
 * Dashboard controller generating KPI summary and charts.
 */
class DashboardController extends Controller
{
    private PDO $db;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->db = Database::getConnection();
    }

    public function overview(): void
    {
        $kpis = [
            'pending_orders' => (int) $this->db->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'PENDING_APPROVAL'")->fetchColumn(),
            'total_suppliers' => (int) $this->db->query("SELECT COUNT(*) FROM suppliers WHERE is_active = 1")->fetchColumn(),
            'low_stock_products' => (int) $this->db->query("SELECT COUNT(*) FROM products p JOIN inventory i ON p.id = i.product_id WHERE i.quantity <= p.safety_stock")->fetchColumn(),
            'monthly_spend' => (float) $this->db->query("SELECT IFNULL(SUM(total_amount),0) FROM purchase_orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
        ];

        $trendStmt = $this->db->prepare('SELECT DATE_FORMAT(order_date, "%m-%d") AS label, SUM(total_amount) AS value FROM purchase_orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY order_date ORDER BY order_date');
        $trendStmt->execute();
        $trend = $trendStmt->fetchAll();

        $inventoryStmt = $this->db->prepare('SELECT p.product_name, i.quantity, p.safety_stock FROM products p JOIN inventory i ON p.id = i.product_id ORDER BY p.product_name LIMIT 10');
        $inventoryStmt->execute();
        $inventory = $inventoryStmt->fetchAll();

        $this->json([
            'success' => true,
            'kpis' => $kpis,
            'trend' => $trend,
            'inventory' => $inventory,
        ]);
    }
}
