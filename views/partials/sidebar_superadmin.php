<?php $user = currentUser(); ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">CHIMS-IQ</div>
        <div class="brand-store">Super Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="<?= BASE_URL ?>views/dashboard/superadmin.php" class="nav-item active">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <div class="nav-section-label">Management</div>
        <a href="#" class="nav-item">
            <span class="nav-icon">🏬</span> Stores
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">👥</span> All Users
        </a>
        <div class="nav-section-label">System</div>
        <a href="#" class="nav-item">
            <span class="nav-icon">⚙️</span> Settings
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">📋</span> Audit Log
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <div>
                <div class="user-info-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-info-role"><?= htmlspecialchars($user['role']) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>controllers/auth/logout.php" class="btn-logout">Sign Out</a>
    </div>
</aside>
