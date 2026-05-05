<?php
require_once '../../autoload.php';
requireRole('superadmin');
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CHIMS-IQ Super Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>

<body>
    <div class="app-layout">
        <?php require_once '../partials/sidebar_superadmin.php'; ?>

        <div class="main-content">
            <div class="topbar">
                <div style="display:flex; align-items:center;">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                    <h1>Dashboard</h1>
                </div>
                <div class="topbar-right">
                    <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp; <span>superadmin</span>
                </div>
            </div>

            <div class="page-body">
                <div class="empty-state">
                    <div class="empty-icon">🛡️</div>
                    <h3>Super Admin Dashboard</h3>
                    <p>Welcome, <?= htmlspecialchars($user['full_name']) ?>. This panel is under construction — store and user management tools will appear here.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>

</html>