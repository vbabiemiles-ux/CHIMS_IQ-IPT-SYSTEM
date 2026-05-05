<?php
require_once '../../autoload.php';
requireRole('admin');

global $db;
$user = currentUser();
$supplier = new Supplier($db);

// Get messages from session
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$msgType = isset($_SESSION['success']) ? 'success' : (isset($_SESSION['error']) ? 'error' : '');

// Clear session messages after displaying
unset($_SESSION['success'], $_SESSION['error']);

$editSupplier = null;
if (isset($_GET['edit'])) {
    $editSupplier = $supplier->getById((int)$_GET['edit']);
}

$suppliers = $supplier->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Suppliers</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <?php if ($message): ?>
            <div class="alert-msg <?= $msgType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- Add / Edit Form -->
            <div class="section-card" style="max-width:600px;">
                <h3><?= $editSupplier ? '✏️ Edit Supplier' : '➕ Add Supplier' ?></h3>
                <form method="POST" action="<?= BASE_URL ?>controllers/suppliers/<?= $editSupplier ? 'update' : 'create' ?>.php">
                    <?= csrf_field() ?>
                    <?php if ($editSupplier): ?>
                        <input type="hidden" name="id" value="<?= $editSupplier['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Supplier Name *</label>
                        <input type="text" name="supplier_name" class="form-input"
                               value="<?= htmlspecialchars($editSupplier['supplier_name'] ?? '') ?>"
                               placeholder="e.g. PC Express" required>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-input"
                                   value="<?= htmlspecialchars($editSupplier['phone'] ?? '') ?>"
                                   placeholder="09XXXXXXXXX" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input"
                                   value="<?= htmlspecialchars($editSupplier['email'] ?? '') ?>"
                                   placeholder="supplier@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <textarea name="address" class="form-input" rows="2"
                                  placeholder="Full address" required><?= htmlspecialchars($editSupplier['address'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:8px;">
                        <?php if ($editSupplier): ?>
                            <button type="submit" class="btn-sm pri">Update Supplier</button>
                            <a href="<?= BASE_URL ?>views/suppliers/index.php" class="btn-sm out">Cancel</a>
                        <?php else: ?>
                            <button type="submit" class="btn-sm pri">Add Supplier</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Suppliers List -->
            <div class="section-card">
                <h3>All Suppliers (<?= count($suppliers) ?>)</h3>

                <?php if (empty($suppliers)): ?>
                <div class="empty-state" style="min-height:160px;">
                    <div class="empty-icon">🏭</div>
                    <h3>No suppliers yet</h3>
                    <p>Add your first supplier using the form above.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Supplier Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $i => $sup): ?>
                            <tr>
                                <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($sup['supplier_name']) ?></strong></td>
                                <td><?= htmlspecialchars($sup['phone']) ?></td>
                                <td><?= htmlspecialchars($sup['email']) ?></td>
                                <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($sup['address']) ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $sup['id'] ?>" class="btn-sm out">Edit</a>
                                    <form method="POST" action="<?= BASE_URL ?>controllers/suppliers/delete.php" style="display:inline;"
                                          onsubmit="return confirm('Soft-delete this supplier?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $sup['id'] ?>">
                                        <button type="submit" class="btn-sm red">Delete</button>
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
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>