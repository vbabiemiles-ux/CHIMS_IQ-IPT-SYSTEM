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

// Validate required fields
$categoryId = $_POST['id'] ?? null;
$categoryName = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$categoryId || !$categoryName) {
    $_SESSION['error'] = 'Category ID and name are required.';
    header("Location: ../../views/categories/index.php");
    exit;
}

// Update category
$category = new Category($db);
$result = $category->update($categoryId, $categoryName, $description);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/categories/index.php");
exit;
