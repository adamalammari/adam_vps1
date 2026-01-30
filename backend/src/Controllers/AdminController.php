<?php
/**
 * Admin Controller
 * Handles admin authentication and operations
 */
class AdminController
{
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->getConnection();
    }

    /**
     * Admin login
     * POST /api/admin/login
     * Body: {email, password}
     */
    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            Response::error('Invalid JSON', 400);
        }

        $errors = Validator::required(['email', 'password'], $input);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $email = trim($input['email']);
        $password = $input['password'];

        try {
            $stmt = $this->db->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin || !Auth::verifyPassword($password, $admin['password_hash'])) {
                Response::error('Invalid credentials', 401);
            }

            $token = Auth::generateAdminToken($admin['id'], $admin['email']);

            Response::success([
                'token' => $token,
                'admin' => [
                    'id' => (int)$admin['id'],
                    'email' => $admin['email']
                ]
            ], 'Login successful');

        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            Response::serverError('Login failed');
        }
    }

    /**
     * Get all products (admin)
     * GET /api/admin/products
     */
    public function getProducts()
    {
        AuthMiddleware::verifyAdmin();

        try {
            $stmt = $this->db->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll();

            Response::success(['products' => $products]);

        } catch (PDOException $e) {
            error_log("Get products error: " . $e->getMessage());
            Response::serverError('Failed to fetch products');
        }
    }

    /**
     * Create product
     * POST /api/admin/products
     */
    public function createProduct()
    {
        AuthMiddleware::verifyAdmin();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            Response::error('Invalid JSON', 400);
        }

        $errors = Validator::required(['name', 'price'], $input);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO products (name, price, description, image_url, category, contact_link, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $input['name'],
                $input['price'],
                $input['description'] ?? null,
                $input['image_url'] ?? null,
                $input['category'] ?? null,
                $input['contact_link'] ?? null,
                $input['is_active'] ?? 1
            ]);

            $productId = $this->db->lastInsertId();

            Response::success([
                'product_id' => $productId
            ], 'Product created successfully', 201);

        } catch (PDOException $e) {
            error_log("Create product error: " . $e->getMessage());
            Response::serverError('Failed to create product');
        }
    }

    /**
     * Update product
     * PUT /api/admin/products/{id}
     */
    public function updateProduct($id)
    {
        AuthMiddleware::verifyAdmin();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            Response::error('Invalid JSON', 400);
        }

        try {
            // Check if product exists
            $stmt = $this->db->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                Response::notFound('Product not found');
            }

            $stmt = $this->db->prepare("
                UPDATE products 
                SET name = ?, price = ?, description = ?, image_url = ?, 
                    category = ?, contact_link = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $input['name'],
                $input['price'],
                $input['description'] ?? null,
                $input['image_url'] ?? null,
                $input['category'] ?? null,
                $input['contact_link'] ?? null,
                $input['is_active'] ?? 1,
                $id
            ]);

            Response::success(null, 'Product updated successfully');

        } catch (PDOException $e) {
            error_log("Update product error: " . $e->getMessage());
            Response::serverError('Failed to update product');
        }
    }

    /**
     * Delete product
     * DELETE /api/admin/products/{id}
     */
    public function deleteProduct($id)
    {
        AuthMiddleware::verifyAdmin();

        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                Response::notFound('Product not found');
            }

            Response::success(null, 'Product deleted successfully');

        } catch (PDOException $e) {
            error_log("Delete product error: " . $e->getMessage());
            Response::serverError('Failed to delete product');
        }
    }

    /**
     * Get settings
     * GET /api/admin/settings
     */
    public function getSettings()
    {
        AuthMiddleware::verifyAdmin();

        try {
            $stmt = $this->db->query("SELECT * FROM settings WHERE id = 1");
            $settings = $stmt->fetch();

            if (!$settings) {
                // Create default settings
                $this->db->exec("
                    INSERT INTO settings (id, app_name, default_contact_link, welcome_message) 
                    VALUES (1, 'Flutter Chat', '', 'Welcome!')
                ");
                $settings = $this->db->query("SELECT * FROM settings WHERE id = 1")->fetch();
            }

            Response::success(['settings' => $settings]);

        } catch (PDOException $e) {
            error_log("Get settings error: " . $e->getMessage());
            Response::serverError('Failed to fetch settings');
        }
    }

    /**
     * Update settings
     * PUT /api/admin/settings
     */
    public function updateSettings()
    {
        AuthMiddleware::verifyAdmin();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            Response::error('Invalid JSON', 400);
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE settings 
                SET app_name = ?, default_contact_link = ?, welcome_message = ?, updated_at = NOW()
                WHERE id = 1
            ");

            $stmt->execute([
                $input['app_name'] ?? 'Flutter Chat',
                $input['default_contact_link'] ?? '',
                $input['welcome_message'] ?? ''
            ]);

            Response::success(null, 'Settings updated successfully');

        } catch (PDOException $e) {
            error_log("Update settings error: " . $e->getMessage());
            Response::serverError('Failed to update settings');
        }
    }
}
