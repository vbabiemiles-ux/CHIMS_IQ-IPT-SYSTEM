<?php
require_once __DIR__ . "/autoload.php";
global $db;

echo "<h1>CHIMS-IQ v3.0 — Phase 0 Verification</h1>";

$tables = [
    'stores', 'users', 'suppliers', 'categories', 'products',
    'product_supplier', 'stock', 'stock_flags', 'purchase_orders',
    'purchase_order_items', 'deletion_log', 'backup_snapshots'
];

echo "<h3>Checking 12 Tables:</h3>";
$allGood = true;

foreach ($tables as $table) {
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    if ($stmt->fetch()) {
        echo "✅ $table<br>";
    } else {
        echo "❌ $table <strong>(MISSING — run schema.sql)</strong><br>";
        $allGood = false;
    }
}

echo "<h3>Checking tenantScope() helper:</h3>";
if (function_exists('tenantScope')) {
    echo "✅ tenantScope() exists<br>";
} else {
    echo "❌ tenantScope() missing — check auth_helper.php<br>";
    $allGood = false;
}

echo "<h3>Checking softDeleteRecord() helper:</h3>";
if (function_exists('softDeleteRecord')) {
    echo "✅ softDeleteRecord() exists<br>";
} else {
    echo "❌ softDeleteRecord() missing — check soft_delete_helper.php<br>";
    $allGood = false;
}

echo "<h3>Checking Superadmin:</h3>";
$stmt = $db->prepare("SELECT id, full_name, role FROM users WHERE role = 'superadmin' LIMIT 1");
$stmt->execute();
if ($super = $stmt->fetch()) {
    echo "✅ Superadmin: " . htmlspecialchars($super['full_name']) . "<br>";
} else {
    echo "⚠️ No superadmin — run seeder.php<br>";
    $allGood = false;
}

echo "<br>";
if ($allGood) {
    echo "<h2 style='color:#00e5a0'>✅ PHASE 0 PASSED — Ready for Phase 1</h2>";
} else {
    echo "<h2 style='color:#f85149'>❌ Phase 0 has issues. Fix before continuing.</h2>";
}