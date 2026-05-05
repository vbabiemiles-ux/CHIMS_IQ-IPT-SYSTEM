<?php
global $db;
require_once '../../autoload.php';

requireLogin();

// Check CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Check permissions
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['admin', 'superadmin'], true)) {
    $_SESSION['error'] = 'You do not have permission to update products.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Validate required fields
$productId = $_POST['id'] ?? null;
$categoryId = $_POST['category_id'] ?? null;
$productName = trim($_POST['product_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = $_POST['price'] ?? 0;
$brand = trim($_POST['brand'] ?? '');

if (!$productId || !$categoryId || !$productName) {
    $_SESSION['error'] = 'Product ID, category, and product name are required.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Update product
$product = new Product($db);
$result = $product->update($productId, $categoryId, $productName, $description, $price, $brand);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: " . BASE_URL . "views/products/index.php");
exit;
