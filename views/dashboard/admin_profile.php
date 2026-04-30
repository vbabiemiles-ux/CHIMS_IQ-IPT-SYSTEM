<?php
require_once '../../autoload.php';
requireRole('admin');
$user = currentUser();

// Fetch store details
$storeData = [];
if ($user['store_id']) {
    $stmt = $db->prepare("SELECT * FROM stores WHERE id = ? LIMIT 1");
    $stmt->execute([$user['store_id']]);
    $storeData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_store'])) {
    $storeName = trim($_POST['store_name'] ?? '');
    if (!$storeName) {
        $error = 'Store name cannot be empty.';
    } else {
        $stmt = $db->prepare("UPDATE stores SET name = ? WHERE id = ?");
        $stmt->execute([$storeName, $user['store_id']]);
        $_SESSION['store_name'] = $storeName;
        $success = 'Store details updated.';
        $storeData['name'] = $storeName;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Details — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 800px; }
        .section-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; }
        .section-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 20px; color: var(--text-main); }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: .88rem; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--text-muted); }
        .info-row .value { color: var(--text-main); font-weight: 500; }
        @media(max-width:700px){ .profile-grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Store Details</h1>
            </div>
            <div class="topbar-right"><?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp; <span>admin</span></div>
        </div>
        <div class="page-body">
            <?php if ($error): ?><div class="alert-msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="profile-grid">
                <!-- Admin Info -->
                <div class="section-card">
                    <h3>👤 My Profile</h3>
                    <div class="info-row"><span class="label">Full Name</span><span class="value"><?= htmlspecialchars($user['full_name']) ?></span></div>
                    <div class="info-row"><span class="label">Email</span><span class="value"><?= htmlspecialchars($user['email']) ?></span></div>
                    <div class="info-row"><span class="label">Role</span><span class="value" style="color:var(--accent);">Admin</span></div>
                </div>

                <!-- Store Info / Edit -->
                <div class="section-card">
                    <h3>🏬 Store Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Store Name</label>
                            <input type="text" name="store_name" class="form-input"
                                   value="<?= htmlspecialchars($storeData['name'] ?? '') ?>" required>
                        </div>
                        <div class="info-row">
                            <span class="label">Store ID</span>
                            <span class="value">#<?= $user['store_id'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Created</span>
                            <span class="value"><?= isset($storeData['created_at']) ? date('M d, Y', strtotime($storeData['created_at'])) : '—' ?></span>
                        </div>
                        <button type="submit" name="update_store" class="btn-submit" style="margin-top:16px;">Update Store</button>
                    </form>
                </div>
            </div>
        </div>
    <?php require_once '../partials/footer.php'; ?>
    </div>
    <script>function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); }</script>
</div>
<script>function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); }</script>
</body>
</html>
