<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Utils\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Purchase order controller handling lifecycle management.
 */
class PurchaseOrderController extends Controller
{
    private PurchaseOrder $purchaseOrders;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->purchaseOrders = new PurchaseOrder();
    }

    public function generateNumber(): void
    {
        $this->json([
            'success' => true,
            'po_number' => $this->purchaseOrders->generatePoNumber(),
        ]);
    }

    public function store(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'supplier_id' => 'required',
                'order_date' => 'required',
                'requested_delivery_date' => 'required',
            ]);

            $items = $payload['items'] ?? [];
            if (empty($items)) {
                throw new InvalidArgumentException(json_encode(['items' => ['발주 품목을 추가하세요.']], JSON_UNESCAPED_UNICODE));
            }

            $total = 0;
            foreach ($items as &$item) {
                Validator::validate($item, [
                    'product_id' => 'required',
                    'quantity' => 'required|min:1',
                    'unit_price' => 'required|min:0',
                ]);
                $item['amount'] = (float) $item['quantity'] * (float) $item['unit_price'];
                $total += $item['amount'];
            }
            unset($item);

            $payload['po_number'] = $payload['po_number'] ?? $this->purchaseOrders->generatePoNumber();
            $payload['total_amount'] = $total;
            $payload['vat_amount'] = $total * 0.1;
            $payload['created_by'] = $this->request->getAttribute('user_id');

            $id = $this->purchaseOrders->create($payload, $items);
            $this->log('CREATE', 'PURCHASE_ORDER', $id, [], $payload);

            $this->json(['success' => true, 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function show(int $id): void
    {
        $po = $this->purchaseOrders->find($id);
        if (!$po) {
            Response::error('발주서를 찾을 수 없습니다.', 404);
            return;
        }

        $po['po_number'] = htmlspecialchars($po['po_number']);
        $this->json(['success' => true, 'data' => $po]);
    }
}
