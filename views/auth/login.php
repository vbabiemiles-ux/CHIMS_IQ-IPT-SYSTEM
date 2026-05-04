<?php
global $db;
require_once '../../autoload.php';
redirectIfLoggedIn();

$role = $_GET['role'] ?? 'admin';
$role = in_array($role, ['admin','staff']) ? $role : 'admin';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $auth   = new Auth($db);
        $result = $auth->login($email, $password);

        if (!$result['status']) {
            $error = $result['message'];
        } else {
            // Redirect based on role
            $userRole = $result['role'];
            if ($userRole === 'superadmin') {
                header("Location: " . BASE_URL . "views/dashboard/superadmin.php");
            } elseif ($userRole === 'admin') {
                header("Location: " . BASE_URL . "views/dashboard/admin.php");
            } else {
                header("Location: " . BASE_URL . "views/dashboard/staff.php");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">CHIMS-IQ</div>
        <div class="auth-logo-sub">Smart Hardware IMS V3.0</div>

        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab active">Sign In</button>
            <?php if ($role === 'admin'): ?>
            <a href="/views/auth/register.php" class="auth-tab" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">Register</a>
            <?php else: ?>
            <button class="auth-tab" style="opacity:.4; cursor:not-allowed;" title="Contact your admin to register">Register</button>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
        <div class="alert-msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input"
                       placeholder="Enter your email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pw" class="form-input"
                           placeholder="Enter your password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw()">👁</button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="auth-divider">or</div>

        <?php if ($role === 'admin'): ?>
        <div class="auth-switch">
            New store owner? <a href="/views/auth/register.php">Register your store →</a>
        </div>
        <?php else: ?>
        <div class="auth-switch">
            <a href="/index.php">← Back to home</a>
        </div>
        <?php endif; ?>

        <div style="margin-top:24px; text-align:center;">
            <a href="/index.php" style="font-size:.78rem; color:var(--text-muted);">← Back to home</a>
        </div>
    </div>
</div>
<script>
function togglePw(){
    var p = document.getElementById('pw');
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
