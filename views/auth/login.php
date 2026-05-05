<?php
global $db;
require_once '../../autoload.php';
redirectIfLoggedIn();

$role = $_GET['role'] ?? 'admin';
$role = in_array($role, ['admin', 'staff'], true) ? $role : 'admin';

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
            $userRole = $result['role'];
            if ($userRole === 'superadmin') {
                header('Location: ' . BASE_URL . 'views/dashboard/superadmin.php');
            } elseif ($userRole === 'admin') {
                header('Location: ' . BASE_URL . 'views/dashboard/admin.php');
            } else {
                header('Location: ' . BASE_URL . 'views/dashboard/staff.php');
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
<main class="auth-page">
    <article class="auth-card">
        <header class="auth-card-header">
            <h1 class="auth-logo">CHIMS-IQ</h1>
            <p class="auth-logo-sub">Smart Hardware IMS V3.0</p>
        </header>

        <nav class="auth-tabs" aria-label="Account">
            <a href="<?= BASE_URL ?>views/auth/login.php?role=<?= htmlspecialchars($role) ?>"
               class="auth-tab active"
               aria-current="page">Sign In</a>
            <?php if ($role === 'admin'): ?>
                <a href="<?= BASE_URL ?>views/auth/register.php" class="auth-tab">Register</a>
            <?php else: ?>
                <span class="auth-tab auth-tab--disabled" title="Contact your admin to register" aria-disabled="true">Register</span>
            <?php endif; ?>
        </nav>

        <?php if ($error): ?>
            <div class="alert-msg error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="auth-section" aria-labelledby="signin-heading">
            <h2 id="signin-heading" class="visually-hidden">Sign in with email</h2>
            <form method="post" class="auth-form">
                <div class="form-group">
                    <label class="form-label" for="login-email">Email address</label>
                    <input id="login-email" type="email" name="email" class="form-input"
                           placeholder="Enter your email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="login-password">Password</label>
                    <div class="input-wrap">
                        <input id="login-password" type="password" name="password" class="form-input"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="toggle-pw" aria-label="Show or hide password" onclick="togglePw('login-password')">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Sign In</button>
            </form>
        </section>

        <?php if ($role === 'admin'): ?>
            <p class="auth-divider" role="separator"><span>or</span></p>
            <section class="auth-section auth-section--compact" aria-labelledby="register-cta-heading">
                <h2 id="register-cta-heading" class="visually-hidden">New store</h2>
                <p class="auth-switch">
                    New store owner?
                    <a href="<?= BASE_URL ?>views/auth/register.php">Register your store →</a>
                </p>
            </section>
        <?php endif; ?>

        <footer class="auth-card-footer">
            <nav aria-label="Site">
                <a href="<?= BASE_URL ?>index.php" class="auth-link-home">← Back to home</a>
            </nav>
        </footer>
    </article>
</main>
<script>
function togglePw(id) {
    var p = document.getElementById(id);
    if (!p) return;
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
