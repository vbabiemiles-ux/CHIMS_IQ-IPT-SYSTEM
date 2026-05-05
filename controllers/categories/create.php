<?php
global $db;
require_once '../../autoload.php';

requireRole('admin');

// Check CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header("Location: " . BASE_URL . "views/categories/index.php");
    exit;
}

// Validate required fields
$categoryName = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$categoryName) {
    $_SESSION['error'] = 'Category name is required.';
    header("Location: " . BASE_URL . "views/categories/index.php");
    exit;
}

// Create category
$category = new Category($db);
$result = $category->create($categoryName, $description);

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: " . BASE_URL . "views/categories/index.php");
exit;
