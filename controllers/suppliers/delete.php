<?php
global $db;
require_once '../../autoload.php';

requireRole('admin');

// Check CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header("Location: ../../views/suppliers/index.php");
    exit;
}

// Validate supplier ID
$supplierId = $_POST['id'] ?? null;
if (!$supplierId) {
    $_SESSION['error'] = 'Supplier ID is required.';
    header("Location: ../../views/suppliers/index.php");
    exit;
}

// Soft delete supplier
$supplier = new Supplier($db);
$result = $supplier->softDelete($supplierId);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/suppliers/index.php");
exit;
