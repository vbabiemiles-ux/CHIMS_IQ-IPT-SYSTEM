<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Database connection failed.");
}

echo "<h2>CHIMS-IQ v3.0 Seeder</h2>";

try {
    // Seed superadmin if not exists
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
    $stmt->execute();

    if (!$stmt->fetch()) {
        $hashed = password_hash('chimsiq2026', PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "INSERT INTO users (full_name, email, password, role, store_id)
             VALUES (?, ?, ?, 'superadmin', NULL)"
        );
        $stmt->execute(['Super Admin', 'chimsiq@gmail.com', $hashed]);
        echo "✅ Superadmin created — chimsiq@gmail.com / chimsiq2026<br>";
    } else {
        echo "ℹ️ Superadmin already exists.<br>";
    }

    echo "<br><strong>✅ Phase 0 Seeding Complete.</strong>";
    echo "<br><small style='color:red'>Delete seeder.php after use.</small>";

} catch (Throwable $e) {
    echo "❌ Seeding Error: " . $e->getMessage();
}