<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE settings 
        SET app_name = ?, default_contact_link = ?, welcome_message = ?
        WHERE id = 1
    ");
    $stmt->execute([
        $_POST['app_name'],
        $_POST['default_contact_link'],
        $_POST['welcome_message']
    ]);
    $success = 'تم تحديث الإعدادات بنجاح';
}

// Get settings
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
if (!$settings) {
    // Create default settings if not exists
    $pdo->exec("INSERT INTO settings (id, app_name) VALUES (1, 'Flutter Chat')");
    $settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
}

$page_title = 'الإعدادات';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <h2>إعدادات التطبيق</h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">اسم التطبيق</label>
                    <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($settings['app_name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">رابط التواصل الافتراضي</label>
                    <input type="url" class="form-control" name="default_contact_link" value="<?php echo htmlspecialchars($settings['default_contact_link'] ?? ''); ?>" placeholder="https://wa.me/1234567890">
                    <small class="form-text text-muted">رابط واتساب أو أي رابط تواصل آخر</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">رسالة الترحيب</label>
                    <textarea class="form-control" name="welcome_message" rows="3"><?php echo htmlspecialchars($settings['welcome_message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
