<?php
require_once '../../autoload.php';
redirectIfLoggedIn();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $storeName   = trim($_POST['store_name'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!$fullName || !$email || !$storeName || !$password || !$confirmPass) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPass) {
        $error = 'Passwords do not match.';
    } else {
        $auth   = new Auth($db);
        $result = $auth->registerAdmin($fullName, $email, $storeName, $password);

        if (!$result['status']) {
            $error = $result['message'];
        } else {
            $success = 'Store registered! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Store — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">CHIMS-IQ</div>
        <div class="auth-logo-sub">Smart Hardware IMS V3.0</div>

        <div class="auth-tabs">
            <a href="/views/auth/login.php?role=admin" class="auth-tab" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">Sign In</a>
            <button class="auth-tab active">Register</button>
        </div>

        <div style="margin-bottom:24px;">
            <h2 style="font-size:1.4rem;">Register <span style="color:var(--accent)">Store</span></h2>
            <p style="color:var(--text-muted); font-size:.85rem; margin-top:4px;">Create your admin account and store</p>
        </div>

        <?php if ($error): ?>
        <div class="alert-msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert-msg success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-input"
                       placeholder="Juan Dela Cruz"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-input"
                       placeholder="juan@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Store Name *</label>
                <input type="text" name="store_name" class="form-input"
                       placeholder="TechSource PH"
                       value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>" required>
                <p style="font-size:.75rem; color:var(--text-muted); margin-top:6px;">
                    This will be the default password for staff you add later.
                </p>
            </div>
            <div class="form-group">
                <label class="form-label">Password *</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pw1" class="form-input"
                           placeholder="Min 8 characters" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw1')">👁</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password *</label>
                <div class="input-wrap">
                    <input type="password" name="confirm_password" id="pw2" class="form-input"
                           placeholder="Repeat your password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('pw2')">👁</button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Create Store Account</button>
        </form>

        <div class="auth-switch" style="margin-top:16px;">
            Already have an account? <a href="/views/auth/login.php?role=admin">Sign in →</a>
        </div>
    </div>
</div>
<script>
function togglePw(id){
    var p = document.getElementById(id);
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
