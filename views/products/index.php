<?php
require_once '../../autoload.php';
requireRole('admin');

global $db;
$user            = currentUser();
$product         = new Product($db);
$category        = new Category($db);
$productSupplier = new ProductSupplier($db);
$supplierObj     = new Supplier($db);

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';
    } elseif (isset($_POST['create_product'])) {
        $result  = $product->create(
            $_POST['category_id'],
            $_POST['product_name'],
            $_POST['description'] ?? '',
            $_POST['price'] ?? 0,
            $_POST['brand'] ?? ''
        );
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['update_product'])) {
        $result  = $product->update(
            $_POST['id'],
            $_POST['category_id'],
            $_POST['product_name'],
            $_POST['description'] ?? '',
            $_POST['price'] ?? 0,
            $_POST['brand'] ?? ''
        );
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['soft_delete'])) {
        $result  = $product->softDelete($_POST['id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['link_supplier'])) {
        $result  = $productSupplier->link($_POST['product_id'], $_POST['supplier_id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['unlink_supplier'])) {
        $result  = $productSupplier->unlink($_POST['product_id'], $_POST['supplier_id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = $product->getById((int)$_GET['edit']);
}

$products   = $product->getAll();
$categories = $category->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .health-dot {
            width: 8px; height: 8px; border-radius: 50%;
            display: inline-block; margin-right: 6px;
        }
        .dot-gd  { background: var(--accent); }
        .dot-lo  { background: var(--warning); }
        .dot-cr  { background: var(--danger); }
        .linked-supplier {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: .85rem;
        }
        @media(max-width:700px){ .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Products</h1>
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

            <!-- No categories warning -->
            <?php if (empty($categories)): ?>
            <div class="alert-msg error">
                ⚠️ No categories found. 
                <a href="/views/categories/index.php" style="color:var(--danger); text-decoration:underline;">
                    Add a category first
                </a> before creating products.
            </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 420px 1fr; gap:24px; align-items:start;">

                <!-- LEFT: Add / Edit Form -->
                <div>
                    <div class="section-card">
                        <h3><?= $editProduct ? '✏️ Edit Product' : '➕ Add Product' ?></h3>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <?php if ($editProduct): ?>
                                <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-input" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="product_name" class="form-input"
                                       value="<?= htmlspecialchars($editProduct['product_name'] ?? '') ?>"
                                       placeholder="e.g. RTX 4060 Ti" required>
                            </div>

                            <div class="two-col">
                                <div class="form-group">
                                    <label class="form-label">Brand</label>
                                    <input type="text" name="brand" class="form-input"
                                           value="<?= htmlspecialchars($editProduct['brand'] ?? '') ?>"
                                           placeholder="e.g. ASUS">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Price (₱) *</label>
                                    <input type="number" step="0.01" min="0"
                                           name="price" class="form-input"
                                           value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>"
                                           placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input" rows="3"
                                          placeholder="Optional product description"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                            </div>

                            <div style="display:flex; gap:10px; margin-top:8px;">
                                <?php if ($editProduct): ?>
                                    <button type="submit" name="update_product" class="btn-sm pri">Update Product</button>
                                    <a href="/views/products/index.php" class="btn-sm out">Cancel</a>
                                <?php else: ?>
                                    <button type="submit" name="create_product" class="btn-sm pri">Add Product</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Supplier Linking Panel (edit mode only) -->
                    <?php if ($editProduct): ?>
                    <div class="section-card">
                        <h3>🔗 Linked Suppliers</h3>
                        <p style="font-size:.8rem; color:var(--text-muted); margin-bottom:14px;">
                            Link suppliers that provide
                            <strong><?= htmlspecialchars($editProduct['product_name']) ?></strong>
                        </p>

                        <?php
                        $linked      = $productSupplier->getByProduct($editProduct['id']);
                        $allSuppliers = $supplierObj->getAll();
                        // Filter out already-linked suppliers from dropdown
                        $linkedIds   = array_column($linked, 'id');
                        $available   = array_filter($allSuppliers, fn($s) => !in_array($s['id'], $linkedIds));
                        ?>

                        <!-- Link new supplier -->
                        <?php if (!empty($available)): ?>
                        <form method="POST" style="display:flex; gap:8px; margin-bottom:16px; align-items:center;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                            <select name="supplier_id" class="form-input" style="flex:1;">
                                <option value="">Select supplier…</option>
                                <?php foreach ($available as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['supplier_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="link_supplier" class="btn-sm pri">Link</button>
                        </form>
                        <?php else: ?>
                        <p style="font-size:.82rem; color:var(--text-muted); margin-bottom:14px;">
                            All available suppliers are already linked.
                        </p>
                        <?php endif; ?>

                        <!-- Linked list -->
                        <?php if (!empty($linked)): ?>
                            <?php foreach ($linked as $s): ?>
                            <div class="linked-supplier">
                                <div>
                                    <strong><?= htmlspecialchars($s['supplier_name']) ?></strong>
                                    <span style="color:var(--text-muted); margin-left:8px; font-size:.78rem;">
                                        <?= htmlspecialchars($s['phone']) ?>
                                    </span>
                                </div>
                                <form method="POST"
                                      onsubmit="return confirm('Unlink this supplier?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                                    <input type="hidden" name="supplier_id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="unlink_supplier" class="btn-sm red">Unlink</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size:.82rem; color:var(--text-muted);">No suppliers linked yet.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: Products Table -->
                <div class="section-card" style="margin-bottom:0;">
                    <h3>All Products (<?= count($products) ?>)</h3>

                    <?php if (empty($products)): ?>
                    <div class="empty-state" style="min-height:200px;">
                        <div class="empty-icon">📦</div>
                        <h3>No products yet</h3>
                        <p>Add your first product using the form.</p>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Health</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <?php
                                    $h = strtolower($p['health_status'] ?? 'critical');
                                    $dotClass = $h === 'healthy' ? 'dot-gd' : ($h === 'low' ? 'dot-lo' : 'dot-cr');
                                    $bdgClass = $h === 'healthy' ? 'gd'     : ($h === 'low' ? 'lo'    : 'cr');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['product_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($p['brand'] ?: '—') ?></td>
                                    <td>₱<?= number_format($p['price'], 2) ?></td>
                                    <td style="text-align:center;">
                                        <?= $p['quantity'] ?? 0 ?>
                                    </td>
                                    <td>
                                        <span class="health-dot <?= $dotClass ?>"></span>
                                        <span class="bdg <?= $bdgClass ?>">
                                            <?= htmlspecialchars($p['health_status'] ?? 'Critical') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $p['id'] ?>" class="btn-sm out">Edit</a>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Soft-delete this product?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="soft_delete" value="1">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

            </div><!-- end grid -->
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