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
    $_SESSION['error'] = 'You do not have permission to unlink suppliers.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Validate required fields
$productId = $_POST['product_id'] ?? null;
$supplierId = $_POST['supplier_id'] ?? null;

if (!$productId || !$supplierId) {
    $_SESSION['error'] = 'Product ID and supplier ID are required.';
    header("Location: " . BASE_URL . "views/products/index.php");
    exit;
}

// Unlink supplier from product
$productSupplier = new ProductSupplier($db);
$result = $productSupplier->unlink($productId, $supplierId);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: " . BASE_URL . "views/products/index.php");
exit;
