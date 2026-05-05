<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define BASE_URL
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', $protocol . $host . $base_path . '/');
}

// Database
require_once __DIR__ . "/config/database.php";

// Helpers
require_once __DIR__ . "/helpers/csrf_helper.php";
require_once __DIR__ . "/helpers/encrypt_helper.php";
require_once __DIR__ . "/helpers/auth_helper.php";
require_once __DIR__ . "/helpers/soft_delete_helper.php";

// Auto-load classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . "/classes/$class.php";
    if (file_exists($file)) {
        require_once $file;
    }
});

// Create shared database connection
$database = new Database();
$db = $database->getConnection();
