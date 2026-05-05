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
$supplierName = trim($_POST['supplier_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!$supplierName) {
    $_SESSION['error'] = 'Supplier name is required.';
    header("Location: ../../views/suppliers/index.php");
    exit;
}

// Create supplier
$supplier = new Supplier($db);
$result = $supplier->create($supplierName, $phone, $email, $address);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/suppliers/index.php");
exit;
