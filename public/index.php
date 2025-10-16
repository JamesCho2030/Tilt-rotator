<?php
// Simple PSR-4 autoloader for the application
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Handle CORS for API usage
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$request = new App\Core\Request();
$router = new App\Core\Router();
$authMiddleware = new App\Middleware\AuthMiddleware();
$adminMiddleware = new App\Middleware\RoleMiddleware(['ADMIN']);
$buyerMiddleware = new App\Middleware\RoleMiddleware(['ADMIN', 'BUYER']);
$approverMiddleware = new App\Middleware\RoleMiddleware(['ADMIN', 'APPROVER']);

// Public routes
$router->add('POST', '/api/login', function (App\Core\Request $request) {
    (new App\Controllers\AuthController($request))->login();
});

// Authenticated routes
$router->add('GET', '/api/profile', function (App\Core\Request $request) {
    (new App\Controllers\AuthController($request))->profile();
}, [$authMiddleware]);

$router->add('GET', '/api/dashboard', function (App\Core\Request $request) {
    (new App\Controllers\DashboardController($request))->overview();
}, [$authMiddleware]);

// Product routes
$router->add('GET', '/api/products', function (App\Core\Request $request) {
    (new App\Controllers\ProductController($request))->index();
}, [$authMiddleware]);
$router->add('POST', '/api/products', function (App\Core\Request $request) {
    (new App\Controllers\ProductController($request))->store();
}, [$authMiddleware, $buyerMiddleware]);
$router->add('GET', '/api/products/{id}', function (App\Core\Request $request, $id) {
    (new App\Controllers\ProductController($request))->show((int) $id);
}, [$authMiddleware]);
$router->add('PUT', '/api/products/{id}', function (App\Core\Request $request, $id) {
    (new App\Controllers\ProductController($request))->update((int) $id);
}, [$authMiddleware, $buyerMiddleware]);
$router->add('DELETE', '/api/products/{id}', function (App\Core\Request $request, $id) {
    (new App\Controllers\ProductController($request))->destroy((int) $id);
}, [$authMiddleware, $adminMiddleware]);

// Supplier routes
$router->add('GET', '/api/suppliers', function (App\Core\Request $request) {
    (new App\Controllers\SupplierController($request))->index();
}, [$authMiddleware]);
$router->add('POST', '/api/suppliers', function (App\Core\Request $request) {
    (new App\Controllers\SupplierController($request))->store();
}, [$authMiddleware, $buyerMiddleware]);
$router->add('PUT', '/api/suppliers/{id}', function (App\Core\Request $request, $id) {
    (new App\Controllers\SupplierController($request))->update((int) $id);
}, [$authMiddleware, $buyerMiddleware]);

// Purchase order routes
$router->add('GET', '/api/purchase-orders/generate-number', function (App\Core\Request $request) {
    (new App\Controllers\PurchaseOrderController($request))->generateNumber();
}, [$authMiddleware, $buyerMiddleware]);
$router->add('POST', '/api/purchase-orders', function (App\Core\Request $request) {
    (new App\Controllers\PurchaseOrderController($request))->store();
}, [$authMiddleware, $buyerMiddleware]);
$router->add('GET', '/api/purchase-orders/{id}', function (App\Core\Request $request, $id) {
    (new App\Controllers\PurchaseOrderController($request))->show((int) $id);
}, [$authMiddleware]);

// Inventory routes
$router->add('GET', '/api/inventory/{productId}', function (App\Core\Request $request, $productId) {
    (new App\Controllers\InventoryController($request))->show((int) $productId);
}, [$authMiddleware]);
$router->add('POST', '/api/inventory/adjust', function (App\Core\Request $request) {
    (new App\Controllers\InventoryController($request))->adjust();
}, [$authMiddleware, $buyerMiddleware]);
$router->add('POST', '/api/inventory/receive', function (App\Core\Request $request) {
    (new App\Controllers\InventoryController($request))->receive();
}, [$authMiddleware, $approverMiddleware]);

$router->dispatch($request);
