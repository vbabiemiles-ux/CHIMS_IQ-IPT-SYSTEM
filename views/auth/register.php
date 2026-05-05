<?php
global $db;
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
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <main class="auth-page">
        <article class="auth-card">
            <header class="auth-card-header">
                <h1 class="auth-logo">CHIMS-IQ</h1>
                <p class="auth-logo-sub">Smart Hardware IMS V3.0</p>
            </header>

            <nav class="auth-tabs" aria-label="Account">
                <a href="login.php?role=admin" class="auth-tab">Sign In</a>
                <a href="register.php" class="auth-tab active" aria-current="page">Register</a>
            </nav>

            <section class="auth-section auth-section--intro" aria-labelledby="register-heading">
                <h2 id="register-heading" class="auth-register-title">Register <span class="auth-register-accent">store</span></h2>
                <p class="auth-register-lead">Create your admin account and store.</p>
            </section>

            <?php if ($error): ?>
                <div class="alert-msg error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-msg success" role="status"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <section class="auth-section" aria-labelledby="register-form-heading">
                <h2 id="register-form-heading" class="visually-hidden">Registration form</h2>
                <form method="post" class="auth-form">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label" for="reg-full-name">Full name</label>
                        <input id="reg-full-name" type="text" name="full_name" class="form-input"
                            placeholder="Juan Dela Cruz"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reg-email">Email address</label>
                        <input id="reg-email" type="email" name="email" class="form-input"
                            placeholder="juan@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reg-store">Store name</label>
                        <input id="reg-store" type="text" name="store_name" class="form-input"
                            placeholder="TechSource PH"
                            value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>" required autocomplete="organization">
                        <p class="form-hint">This will be the default password for staff you add later.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pw1">Password</label>
                        <div class="input-wrap">
                            <input id="pw1" type="password" name="password" class="form-input"
                                placeholder="Min 8 characters" required autocomplete="new-password" minlength="8">
                            <button type="button" class="toggle-pw" aria-label="Show or hide password" onclick="togglePw('pw1')">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pw2">Confirm password</label>
                        <div class="input-wrap">
                            <input id="pw2" type="password" name="confirm_password" class="form-input"
                                placeholder="Repeat your password" required autocomplete="new-password" minlength="8">
                            <button type="button" class="toggle-pw" aria-label="Show or hide password" onclick="togglePw('pw2')">👁</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Create store account</button>
                </form>
            </section>

            <section class="auth-section auth-section--compact" aria-labelledby="signin-cta-heading">
                <h2 id="signin-cta-heading" class="visually-hidden">Already registered</h2>
                <p class="auth-switch">
                    Already have an account?
                    <a href="login.php?role=admin">Sign in →</a>
                </p>
            </section>

            <footer class="auth-card-footer">
                <nav aria-label="Site">
                    <a href="../../index.php" class="auth-link-home">← Back to home</a>
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