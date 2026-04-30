<?php
require_once '../../autoload.php';
requireRole('admin');
$user = currentUser();

$error   = '';
$success = '';

// Add staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staffName  = trim($_POST['staff_name'] ?? '');
    $staffEmail = trim($_POST['staff_email'] ?? '');
    $staffPass  = $_POST['staff_password'] ?? '';

    if (!$staffName || !$staffEmail || !$staffPass) {
        $error = 'All fields are required.';
    } elseif (strlen($staffPass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check email unique
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$staffEmail]);
        if ($stmt->fetch()) {
            $error = 'Email already in use.';
        } else {
            $hashed = password_hash($staffPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare(
                "INSERT INTO users (store_id, full_name, email, password, role) VALUES (?,?,?,?,'staff')"
            );
            $stmt->execute([$user['store_id'], $staffName, $staffEmail, $hashed]);
            $success = 'Staff member added successfully.';
        }
    }
}

// Delete staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $deleteId = (int)$_POST['staff_id'];
    $stmt = $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ? AND store_id = ? AND role = 'staff'");
    $stmt->execute([$deleteId, $user['store_id']]);
    $success = 'Staff member removed.';
}

// Fetch staff list
$stmt = $db->prepare(
    "SELECT id, full_name, email, created_at FROM users
     WHERE store_id = ? AND role = 'staff' AND deleted_at IS NULL ORDER BY created_at DESC"
);
$stmt->execute([$user['store_id']]);
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .staff-layout { display: grid; grid-template-columns: 340px 1fr; gap: 24px; }
        .section-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; }
        .section-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 20px; }
        .staff-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .staff-table th { text-align: left; padding: 10px 14px; color: var(--text-muted); font-size: .72rem; letter-spacing: 1px; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .staff-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); color: var(--text-main); }
        .staff-table tr:hover td { background: rgba(255,255,255,.02); }
        .btn-del { background: rgba(248,81,73,.1); color: var(--danger); border: 1px solid rgba(248,81,73,.2); border-radius: 6px; padding: 5px 12px; font-size: .78rem; cursor: pointer; }
        .btn-del:hover { background: rgba(248,81,73,.2); }
        .avatar-sm { width:30px; height:30px; background:var(--accent); color:#0d1117; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; margin-right:8px; vertical-align:middle; }
        @media(max-width:800px){ .staff-layout{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Staff Management</h1>
            </div>
            <div class="topbar-right"><?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp; <span>admin</span></div>
        </div>
        <div class="page-body">
            <?php if ($error): ?><div class="alert-msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-msg success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="staff-layout">
                <!-- Add Staff Form -->
                <div class="section-card" style="align-self:start;">
                    <h3>➕ Add Staff Member</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="staff_name" class="form-input" placeholder="e.g. Staff Name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="staff_email" class="form-input" placeholder="staff@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="text" name="staff_password" class="form-input"
                                   placeholder="Min 6 characters"
                                   value="<?= htmlspecialchars($user['store_name'] ?? '') ?>" required>
                            <p style="font-size:.72rem; color:var(--text-muted); margin-top:5px;">Default: store name. Staff can change later.</p>
                        </div>
                        <button type="submit" name="add_staff" class="btn-submit">Add Staff</button>
                    </form>
                </div>

                <!-- Staff List -->
                <div class="section-card">
                    <h3>👥 Staff List <span style="color:var(--text-muted); font-weight:400; font-size:.85rem;">(<?= count($staffList) ?> members)</span></h3>
                    <?php if (empty($staffList)): ?>
                    <div class="empty-state" style="min-height:200px;">
                        <div class="empty-icon">👤</div>
                        <h3>No staff yet</h3>
                        <p>Add your first staff member using the form.</p>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffList as $s): ?>
                                <tr>
                                    <td>
                                        <span class="avatar-sm"><?= strtoupper(substr($s['full_name'],0,1)) ?></span>
                                        <?= htmlspecialchars($s['full_name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Remove this staff member?')">
                                            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                                            <button type="submit" name="delete_staff" class="btn-del">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); }</script>
</body>
</html>
