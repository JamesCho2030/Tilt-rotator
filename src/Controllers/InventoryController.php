<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Inventory;
use App\Utils\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Inventory controller exposing stock tracking endpoints.
 */
class InventoryController extends Controller
{
    private Inventory $inventory;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->inventory = new Inventory();
    }

    public function show(int $productId): void
    {
        $stock = $this->inventory->getProductInventory($productId);
        $this->json(['success' => true, 'data' => $stock]);
    }

    public function adjust(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'product_id' => 'required',
                'transaction_type' => 'required',
                'quantity' => 'required|min:0',
            ]);

            $payload['created_by'] = $this->request->getAttribute('user_id');
            $this->inventory->adjustStock($payload);
            $this->log('UPDATE', 'INVENTORY', $payload['product_id'], [], $payload);
            $this->json(['success' => true]);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function receive(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'purchase_order_item_id' => 'required',
                'quantity' => 'required|min:0',
            ]);
            $this->inventory->receiveGoods((int) $payload['purchase_order_item_id'], (float) $payload['quantity'], (int) $this->request->getAttribute('user_id'));
            $this->json(['success' => true]);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
