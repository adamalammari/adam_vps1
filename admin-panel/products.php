<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/db.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, price, description, image_url, category, contact_link, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['price'],
            $_POST['description'],
            $_POST['image_url'],
            $_POST['category'],
            $_POST['contact_link'],
            isset($_POST['is_active']) ? 1 : 0
        ]);
        $success = 'تم إضافة المنتج بنجاح';
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, price = ?, description = ?, image_url = ?, category = ?, contact_link = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['price'],
            $_POST['description'],
            $_POST['image_url'],
            $_POST['category'],
            $_POST['contact_link'],
            isset($_POST['is_active']) ? 1 : 0,
            $_POST['id']
        ]);
        $success = 'تم تحديث المنتج بنجاح';
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $success = 'تم حذف المنتج بنجاح';
    }
}

// Get all products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

$page_title = 'إدارة المنتجات';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>إدارة المنتجات</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-circle"></i> إضافة منتج
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>الصورة</th>
                    <th>الاسم</th>
                    <th>السعر</th>
                    <th>الفئة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <?php if ($product['image_url']): ?>
                            <img src="<?php echo $product['image_url']; ?>" alt="" style="width: 50px; height: 50px; object-fit: cover;">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($product['category'] ?? '-'); ?></td>
                    <td>
                        <?php if ($product['is_active']): ?>
                            <span class="badge bg-success">نشط</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">غير نشط</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد؟')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة منتج جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">الاسم *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر *</label>
                        <input type="number" step="0.01" class="form-control" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط الصورة</label>
                        <input type="url" class="form-control" name="image_url">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <input type="text" class="form-control" name="category">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رابط التواصل</label>
                        <input type="url" class="form-control" name="contact_link">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                نشط
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
