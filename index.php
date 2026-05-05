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
    <style>
        body {margin:0; font-family:Arial,Helvetica,sans-serif; background:#f5f7fb; color:#111;}
        .landing-page {max-width:960px; margin:0 auto; padding:60px 20px; text-align:center;}
        .landing-logo {font-size:3rem; font-weight:800; letter-spacing:0.1em; color:#1a1f35;}
        .landing-sub {margin:12px 0 24px; font-size:1.1rem; color:#555;}
        .landing-desc {max-width:680px; margin:0 auto 32px; line-height:1.75; color:#444;}
        .feature-cards {display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin:0 auto 32px;}
        .feature-card {background:#fff; border:1px solid #e3e8ef; border-radius:16px; padding:20px; text-align:left; box-shadow:0 12px 25px rgba(17,24,39,0.08);}
        .feature-card .icon {font-size:1.75rem; margin-bottom:12px;}
        .feature-card h4 {margin:0 0 8px; font-size:1.05rem; color:#111827;}
        .feature-card p {margin:0; color:#4b5563; line-height:1.6;}
        .btn-accent {border:none; background:#2563eb; color:#fff; padding:14px 26px; border-radius:999px; font-size:1rem; cursor:pointer; transition:background .2s ease;}
        .btn-accent:hover {background:#1d4ed8;}
        .modal-overlay {position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(15,23,42,0.65); padding:20px; z-index:20;}
        .modal-overlay.active {display:flex;}
        .modal-box {width:100%; max-width:460px; background:#fff; border-radius:24px; padding:28px; box-shadow:0 24px 80px rgba(15,23,42,0.22);}
        .modal-box h2 {margin:0 0 8px; font-size:1.5rem;}
        .modal-box p {margin:0 0 20px; color:#4b5563;}
        .role-options {display:grid; gap:14px;}
        .role-btn {display:flex; gap:14px; align-items:center; padding:16px 18px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:16px; text-decoration:none; color:inherit; transition:transform .15s ease, border-color .15s ease;}
        .role-btn:hover {transform:translateY(-1px); border-color:#cbd5e1;}
        .role-icon {font-size:1.65rem;}
        .role-info strong {display:block; margin-bottom:4px; font-size:1rem; color:#111827;}
        .role-info span {font-size:.95rem; color:#6b7280;}
        .modal-close {margin-top:20px; width:100%; border:none; background:#e5e7eb; color:#111827; padding:14px 18px; border-radius:14px; cursor:pointer; font-size:1rem;}
        .modal-close:hover {background:#d1d5db;}
    </style>
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
