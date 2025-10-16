<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Utils\Validator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Product controller covering CRUD operations and supplier relations.
 */
class ProductController extends Controller
{
    private Product $products;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->products = new Product();
    }

    public function index(): void
    {
        $page = (int) ($this->request->input('page') ?? 1);
        $search = $this->request->input('search');
        $config = require __DIR__ . '/../../config/app.php';
        $result = $this->products->paginate($page, $config['pagination']['per_page'], $search);

        $result['items'] = array_map(function ($item) {
            $item['product_name'] = htmlspecialchars($item['product_name']);
            $item['product_code'] = htmlspecialchars($item['product_code']);
            return $item;
        }, $result['items']);

        $this->json(['success' => true, 'data' => $result]);
    }

    public function store(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'product_code' => 'required',
                'product_name' => 'required',
                'unit' => 'required',
            ]);

            $payload['created_by'] = $this->request->getAttribute('user_id');
            $id = $this->products->create($payload);

            $this->log('CREATE', 'PRODUCT', $id, [], $payload);

            $this->json(['success' => true, 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function show(int $id): void
    {
        $product = $this->products->find($id);
        if (!$product) {
            Response::error('제품을 찾을 수 없습니다.', 404);
            return;
        }

        $product['product_name'] = htmlspecialchars($product['product_name']);
        $product['product_code'] = htmlspecialchars($product['product_code']);
        $product['suppliers'] = $this->products->suppliers($id);
        $this->json(['success' => true, 'data' => $product]);
    }

    public function update(int $id): void
    {
        try {
            $payload = $this->request->all();
            $original = $this->products->find($id);
            if (!$original) {
                Response::error('제품을 찾을 수 없습니다.', 404);
                return;
            }

            $this->products->update($id, $payload);
            $this->log('UPDATE', 'PRODUCT', $id, $original, $payload);
            $this->json(['success' => true]);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): void
    {
        $product = $this->products->find($id);
        if (!$product) {
            Response::error('제품을 찾을 수 없습니다.', 404);
            return;
        }

        $this->products->delete($id);
        $this->log('DELETE', 'PRODUCT', $id, $product, []);
        $this->json(['success' => true]);
    }
}
