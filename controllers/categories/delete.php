<?php
global $db;
require_once '../../autoload.php';

requireRole('admin');

// Check CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header("Location: ../../views/categories/index.php");
    exit;
}

// Validate category ID
$categoryId = $_POST['id'] ?? null;
if (!$categoryId) {
    $_SESSION['error'] = 'Category ID is required.';
    header("Location: ../../views/categories/index.php");
    exit;
}

// Soft delete category
$category = new Category($db);
$result = $category->softDelete($categoryId);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/categories/index.php");
exit;
