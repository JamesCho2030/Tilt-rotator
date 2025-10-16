<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Supplier;
use App\Utils\Validator;
use InvalidArgumentException;

/**
 * Supplier controller managing CRUD operations.
 */
class SupplierController extends Controller
{
    private Supplier $suppliers;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->suppliers = new Supplier();
    }

    public function index(): void
    {
        $list = $this->suppliers->all();
        $list = array_map(function ($supplier) {
            $supplier['supplier_name'] = htmlspecialchars($supplier['supplier_name']);
            return $supplier;
        }, $list);
        $this->json(['success' => true, 'data' => $list]);
    }

    public function store(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'supplier_code' => 'required',
                'supplier_name' => 'required',
                'business_number' => 'required',
            ]);
            $id = $this->suppliers->create($payload);
            $this->log('CREATE', 'SUPPLIER', $id, [], $payload);
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        }
    }

    public function update(int $id): void
    {
        try {
            $payload = $this->request->all();
            $original = $this->suppliers->find($id);
            if (!$original) {
                Response::error('공급업체를 찾을 수 없습니다.', 404);
                return;
            }
            $this->suppliers->update($id, $payload);
            $this->log('UPDATE', 'SUPPLIER', $id, $original, $payload);
            $this->json(['success' => true]);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        }
    }
}
