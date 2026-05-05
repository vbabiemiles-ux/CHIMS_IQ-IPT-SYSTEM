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

// Validate required fields
$supplierId = $_POST['id'] ?? null;
$supplierName = trim($_POST['supplier_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!$supplierId || !$supplierName) {
    $_SESSION['error'] = 'Supplier ID and name are required.';
    header("Location: ../../views/suppliers/index.php");
    exit;
}

// Update supplier
$supplier = new Supplier($db);
$result = $supplier->update($supplierId, $supplierName, $phone, $email, $address);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/suppliers/index.php");
exit;
