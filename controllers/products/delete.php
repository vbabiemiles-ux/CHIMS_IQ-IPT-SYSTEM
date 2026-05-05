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
    $_SESSION['error'] = 'You do not have permission to delete products.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Validate product ID
$productId = $_POST['id'] ?? null;
if (!$productId) {
    $_SESSION['error'] = 'Product ID is required.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Soft delete product
$product = new Product($db);
$result = $product->softDelete($productId);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: " . BASE_URL . "views/products/index.php");
exit;
