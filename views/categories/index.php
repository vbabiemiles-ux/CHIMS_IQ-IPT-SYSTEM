<?php
require_once '../../autoload.php';
requireRole('admin');

global $db;
$user = currentUser();
$category = new Category($db);
$message  = '';
$msgType  = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';
    } elseif (isset($_POST['create_category'])) {
        $result  = $category->create($_POST['category_name'], $_POST['description'] ?? '');
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    } elseif (isset($_POST['update_category'])) {
        $result  = $category->update($_POST['id'], $_POST['category_name'], $_POST['description'] ?? '');
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    } elseif (isset($_POST['soft_delete'])) {
        $result  = $category->softDelete($_POST['id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = $category->getById((int)$_GET['edit']);
}

$categories = $category->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Categories</h1>
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
            <div class="section-card" style="max-width:520px;">
                <h3><?= $editCategory ? '✏️ Edit Category' : '➕ Add Category' ?></h3>
                <form method="POST">
                    <?= csrf_field() ?>
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-input"
                               value="<?= htmlspecialchars($editCategory['category_name'] ?? '') ?>"
                               placeholder="e.g. Graphics Cards" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3"
                                  placeholder="Optional description"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:8px;">
                        <?php if ($editCategory): ?>
                            <button type="submit" name="update_category" class="btn-sm pri">Update Category</button>
                            <a href="/views/categories/index.php" class="btn-sm out">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="create_category" class="btn-sm pri">Add Category</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Category List -->
            <div class="section-card">
                <h3>All Categories (<?= count($categories) ?>)</h3>

                <?php if (empty($categories)): ?>
                <div class="empty-state" style="min-height:160px;">
                    <div class="empty-icon">🏷️</div>
                    <h3>No categories yet</h3>
                    <p>Add your first category using the form above.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $i => $cat): ?>
                            <tr>
                                <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                                <td><?= htmlspecialchars($cat['description'] ?: '—') ?></td>
                                <td><span class="bdg gd">Active</span></td>
                                <td>
                                    <a href="?edit=<?= $cat['id'] ?>" class="btn-sm out">Edit</a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Soft-delete this category?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="soft_delete" value="1">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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