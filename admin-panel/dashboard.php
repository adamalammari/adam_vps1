<?php
require_once 'includes/auth.php';
requireLogin();

$page_title = 'لوحة التحكم';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <h2>مرحباً بك في لوحة التحكم</h2>
    <p>استخدم القائمة أعلاه للتنقل بين الصفحات.</p>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h5 class="card-title mt-3">المنتجات</h5>
                    <a href="products.php" class="btn btn-primary">إدارة المنتجات</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-gear fs-1 text-success"></i>
                    <h5 class="card-title mt-3">الإعدادات</h5>
                    <a href="settings.php" class="btn btn-success">تعديل الإعدادات</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
