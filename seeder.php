<?php

require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

try {
    // Create stores table
    $db->exec("CREATE TABLE IF NOT EXISTS stores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('superadmin','admin','staff') NOT NULL DEFAULT 'staff',
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
    )");

    // Seed superadmin if not exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['chimsiq@gmail.com']);
    $exists = $stmt->fetch();

    if (!$exists) {
        $hashedPassword = password_hash('chimsiq2026', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role, store_id)
                              VALUES (?, ?, ?, 'superadmin', NULL)");
        $stmt->execute(['Super Admin', 'chimsiq@gmail.com', $hashedPassword]);
        echo "Superadmin created.<br>";
    } else {
        echo "Superadmin already exists.<br>";
    }

    echo "Tables created/verified.<br>";
    echo "<br><strong>Setup complete! Delete seeder.php after use.</strong>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
