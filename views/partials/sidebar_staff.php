<?php $user = currentUser(); ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">CHIMS-IQ</div>
        <div class="brand-store"><?= htmlspecialchars($user['store_name'] ?: 'Your Store') ?></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="/views/dashboard/staff.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="nav-section-label">Management</div>
        <a href="#" class="nav-item">
            <span class="nav-icon">📦</span> Products
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">🗃️</span> Stock
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">🚩</span> Flag Items
        </a>

        <div class="nav-section-label">Reports</div>
        <a href="#" class="nav-item">
            <span class="nav-icon">📈</span> Stock Report
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">📑</span> PO History
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
        <a href="/controllers/auth/logout.php" class="btn-logout">Sign Out</a>
    </div>
</aside>
