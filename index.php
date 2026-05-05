<?php
require_once __DIR__ . '/autoload.php';
if (!function_exists('redirectIfLoggedIn')) {
    require_once __DIR__ . '/helpers/auth_helper.php';
}
redirectIfLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHIMS-IQ — Smart Hardware IMS</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="landing-page">
    <div class="landing-logo">CHIMS-IQ</div>
    <div class="landing-sub">Smart Computer Hardware inventory System</div>
    <p class="landing-desc">
        A complete inventory management system built for computer hardware stores —
        track stock, manage suppliers, and automate purchase orders.
    </p>

    <div class="feature-cards">
        <div class="feature-card">
            <div class="icon">📦</div>
            <div>
                <h4>Real-time Stock Health</h4>
                <p>Critical and low-level alerts with auto PO drafts</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="icon">🏭</div>
            <div>
                <h4>Supplier Management</h4>
                <p>Link products to multiple suppliers seamlessly</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="icon">👥</div>
            <div>
                <h4>Role-Based Access</h4>
                <p>Admin full control · Staff read &amp; flag only</p>
            </div>
        </div>
    </div>

    <button class="btn-accent" onclick="document.getElementById('roleModal').classList.add('active')">
        Get Started
    </button>
</div>

<!-- Role Selection Modal -->
<div class="modal-overlay" id="roleModal">
    <div class="modal-box">
        <h2>Welcome to CHIMS-IQ</h2>
        <p>Please select your role to continue</p>
        <div class="role-options">
            <a href="<?= BASE_URL ?>views/auth/login.php?role=admin" class="role-btn">
                <div class="role-icon">🛡️</div>
                <div class="role-info">
                    <strong>Admin / Owner</strong>
                    <span>Full system control &amp; store management</span>
                </div>
            </a>
            <a href="<?= BASE_URL ?>views/auth/login.php?role=staff" class="role-btn">
                <div class="role-icon">👤</div>
                <div class="role-info">
                    <strong>Staff Member</strong>
                    <span>Read access &amp; flag items</span>
                </div>
            </a>
        </div>
        <button class="modal-close" onclick="document.getElementById('roleModal').classList.remove('active')">
            ✕ &nbsp;Cancel
        </button>
    </div>
</div>

<script>
document.getElementById('roleModal').addEventListener('click', function(e){
    if (e.target === this) this.classList.remove('active');
});
</script>
</body>
</html>
