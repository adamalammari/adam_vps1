<?php
/**
 * Main Router
 * Entry point for all API requests
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS headers
header('Access-Control-Allow-Origin: ' . Config::CORS_ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/',
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Middleware/',
        __DIR__ . '/../src/Utils/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

// Route matching
try {
    // Auth routes
    if ($path === '/auth/guest-login' && $method === 'POST') {
        $controller = new AuthController();
        $controller->guestLogin();
    }

    // Chat routes
    elseif ($path === '/chat/messages' && $method === 'GET') {
        $controller = new ChatController();
        $controller->getMessages();
    }
    elseif ($path === '/chat/send' && $method === 'POST') {
        $controller = new ChatController();
        $controller->sendMessage();
    }

    // Upload route
    elseif ($path === '/upload' && $method === 'POST') {
        $controller = new UploadController();
        $controller->upload();
    }

    // Product routes
    elseif ($path === '/products' && $method === 'GET') {
        $controller = new ProductController();
        $controller->getProducts();
    }
    elseif ($path === '/products/categories' && $method === 'GET') {
        $controller = new ProductController();
        $controller->getCategories();
    }
    elseif (preg_match('#^/products/(\d+)$#', $path, $matches) && $method === 'GET') {
        $controller = new ProductController();
        $controller->getProduct($matches[1]);
    }

    // Admin routes
    elseif ($path === '/admin/login' && $method === 'POST') {
        $controller = new AdminController();
        $controller->login();
    }
    elseif ($path === '/admin/products' && $method === 'GET') {
        $controller = new AdminController();
        $controller->getProducts();
    }
    elseif ($path === '/admin/products' && $method === 'POST') {
        $controller = new AdminController();
        $controller->createProduct();
    }
    elseif (preg_match('#^/admin/products/(\d+)$#', $path, $matches) && $method === 'PUT') {
        $controller = new AdminController();
        $controller->updateProduct($matches[1]);
    }
    elseif (preg_match('#^/admin/products/(\d+)$#', $path, $matches) && $method === 'DELETE') {
        $controller = new AdminController();
        $controller->deleteProduct($matches[1]);
    }
    elseif ($path === '/admin/settings' && $method === 'GET') {
        $controller = new AdminController();
        $controller->getSettings();
    }
    elseif ($path === '/admin/settings' && $method === 'PUT') {
        $controller = new AdminController();
        $controller->updateSettings();
    }

    // 404 - Route not found
    else {
        Response::notFound('Endpoint not found');
    }

} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    Response::serverError('Internal server error');
}
