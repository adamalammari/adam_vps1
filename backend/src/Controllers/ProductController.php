<?php
/**
 * Product Controller
 * Handles public product operations
 */
class ProductController
{
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->getConnection();
    }

    /**
     * Get all active products
     * GET /api/products?category=Electronics
     */
    public function getProducts()
    {
        try {
            $category = isset($_GET['category']) ? trim($_GET['category']) : null;

            $sql = "SELECT * FROM products WHERE is_active = 1";
            $params = [];

            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            // Format products
            $formattedProducts = array_map(function($product) {
                return [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'description' => $product['description'],
                    'image_url' => $product['image_url'],
                    'category' => $product['category'],
                    'contact_link' => $product['contact_link'],
                    'created_at' => $product['created_at']
                ];
            }, $products);

            Response::success([
                'products' => $formattedProducts,
                'count' => count($formattedProducts)
            ]);

        } catch (PDOException $e) {
            error_log("Get products error: " . $e->getMessage());
            Response::serverError('Failed to fetch products');
        }
    }

    /**
     * Get single product by ID
     * GET /api/products/{id}
     */
    public function getProduct($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM products 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if (!$product) {
                Response::notFound('Product not found');
            }

            Response::success([
                'product' => [
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'description' => $product['description'],
                    'image_url' => $product['image_url'],
                    'category' => $product['category'],
                    'contact_link' => $product['contact_link'],
                    'created_at' => $product['created_at'],
                    'updated_at' => $product['updated_at']
                ]
            ]);

        } catch (PDOException $e) {
            error_log("Get product error: " . $e->getMessage());
            Response::serverError('Failed to fetch product');
        }
    }

    /**
     * Get all categories
     * GET /api/products/categories
     */
    public function getCategories()
    {
        try {
            $stmt = $this->db->query("
                SELECT DISTINCT category 
                FROM products 
                WHERE is_active = 1 AND category IS NOT NULL AND category != ''
                ORDER BY category ASC
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            Response::success([
                'categories' => $categories,
                'count' => count($categories)
            ]);

        } catch (PDOException $e) {
            error_log("Get categories error: " . $e->getMessage());
            Response::serverError('Failed to fetch categories');
        }
    }
}
