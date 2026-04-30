<?php
require_once '../../autoload.php';
requireRole('admin');
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Dashboard</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp; <span>admin</span>
            </div>
        </div>

        <div class="page-body">
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3>Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h3>
                <p>Your dashboard is empty for now. Stock health, purchase orders, and alerts will appear here as you add products and inventory.</p>
            </div>
        </div>
    </div>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>
