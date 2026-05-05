<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');

$user = currentUser();
$poObj = new PurchaseOrder($db);

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "views/purchaseorder/index.php");
    exit;
}

$order = $poObj->getById((int)$_GET['id']);
if (!$order) {
    $_SESSION['error'] = 'Purchase Order not found or access denied.';
    header("Location: " . BASE_URL . "views/purchaseorder/index.php");
    exit;
}

$totalCost = array_reduce($order['items'] ?? [], function($carry, $item) {
    return $carry + ($item['quantity_ordered'] * $item['unit_cost']);
}, 0);

$st  = $order['status'];
$bdg = $st === 'received' ? 'gd' : ($st === 'cancelled' ? 'cr' : 'lo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?> — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .po-header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .po-total {
            text-align: right;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--accent);
            padding-top: 12px;
            border-top: 1px solid var(--border);
            margin-top: 8px;
        }
        @media(max-width:700px) {
            .po-header-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>
                    PO #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>
                    &nbsp;<span class="bdg <?= $bdg ?>"><?= ucfirst($st) ?></span>
                </h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <div class="po-header-grid">

                <!-- Order Info -->
                <div class="section-card">
                    <h3>📋 Order Details</h3>
                    <div class="info-r">
                        <span class="lb">PO Number</span>
                        <strong style="font-family:monospace;">
                            #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>
                        </strong>
                    </div>
                    <div class="info-r">
                        <span class="lb">Status</span>
                        <span class="bdg <?= $bdg ?>"><?= ucfirst($st) ?></span>
                    </div>
                    <div class="info-r">
                        <span class="lb">Order Date</span>
                        <?= date('F d, Y', strtotime($order['order_date'])) ?>
                    </div>
                    <div class="info-r">
                        <span class="lb">Expected Delivery</span>
                        <?= $order['expected_date']
                            ? date('F d, Y', strtotime($order['expected_date']))
                            : '<span style="color:var(--text-muted)">Not set</span>' ?>
                    </div>
                    <div class="info-r">
                        <span class="lb">Created By</span>
                        <?= htmlspecialchars($order['created_by']) ?>
                    </div>
                    <div class="info-r">
                        <span class="lb">Created At</span>
                        <?= date('M d, Y · g:i A', strtotime($order['created_at'])) ?>
                    </div>
                </div>

                <!-- Supplier Info -->
                <div class="section-card">
                    <h3>🏭 Supplier Details</h3>
                    <div class="info-r">
                        <span class="lb">Supplier</span>
                        <strong><?= htmlspecialchars($order['supplier_name']) ?></strong>
                    </div>
                    <div class="info-r">
                        <span class="lb">Phone</span>
                        <?= htmlspecialchars($order['supplier_phone']) ?>
                    </div>
                    <div class="info-r">
                        <span class="lb">Email</span>
                        <?= htmlspecialchars($order['supplier_email']) ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="section-card">
                <h3>📦 Order Items (<?= count($order['items']) ?>)</h3>

                <?php if (empty($order['items'])): ?>
                <p style="color:var(--text-muted);">No items in this order.</p>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th style="text-align:center;">Qty Ordered</th>
                                <th style="text-align:right;">Unit Cost</th>
                                <th style="text-align:right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $i => $item):
                                $lineTotal = $item['quantity_ordered'] * $item['unit_cost'];
                            ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($item['brand'] ?: '—') ?></td>
                                <td style="text-align:center;">
                                    <?= $item['quantity_ordered'] ?>
                                </td>
                                <td style="text-align:right;">
                                    <?= $item['unit_cost'] > 0
                                        ? '₱' . number_format($item['unit_cost'], 2)
                                        : '<span style="color:var(--text-muted)">TBD</span>' ?>
                                </td>
                                <td style="text-align:right;">
                                    <?= $lineTotal > 0
                                        ? '₱' . number_format($lineTotal, 2)
                                        : '<span style="color:var(--text-muted)">TBD</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="po-total">
                    Total: <?= $totalCost > 0
                        ? '₱' . number_format($totalCost, 2)
                        : '<span style="color:var(--text-muted); font-size:1rem;">
                               Costs pending
                           </span>' ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Back -->
            <div style="margin-top:8px;">
                <a href="<?= BASE_URL ?>views/purchaseorder/index.php" class="btn-sm out">
                    ← Back to Purchase Orders
                </a>
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