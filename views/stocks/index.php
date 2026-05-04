<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');

$user    = currentUser();
$stock   = new Stock($db);
$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';
    } elseif (isset($_POST['update_stock'])) {
        $result  = $stock->updateQuantity(
            (int)$_POST['product_id'],
            (int)$_POST['quantity'],
            isset($_POST['min_stock_level']) ? (int)$_POST['min_stock_level'] : null
        );
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$stockItems = $stock->getAllWithHealth();
$summary    = $stock->getHealthSummary();

// Separate by health for the alert banner
$hasCritical = (int)($summary['critical'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .stock-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .sum-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            text-align: center;
        }
        .sum-val  { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 6px; }
        .sum-lbl  { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .val-total    { color: var(--text-main); }
        .val-healthy  { color: var(--accent); }
        .val-low      { color: var(--warning); }
        .val-critical { color: var(--danger); }

        .inline-form {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
        }
        .stock-input {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 10px;
            color: var(--text-main);
            font-size: .85rem;
            width: 80px;
            outline: none;
        }
        .stock-input:focus { border-color: var(--accent); }

        .stock-input.min-w { width: 65px; }

        .health-row-cr { background: rgba(248,81,73,.04); }
        .health-row-lo { background: rgba(227,179,65,.03); }

        @media(max-width:768px) {
            .stock-summary { grid-template-columns: repeat(2, 1fr); }
            .inline-form   { flex-wrap: wrap; }
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
                <h1>Stock Management</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <!-- Alert message -->
            <?php if ($message): ?>
            <div class="alert-msg <?= $msgType ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- Critical Banner -->
            <?php if ($hasCritical): ?>
            <div class="alert-banner"
                 style="background:linear-gradient(135deg,#7f1d1d,#dc2626);
                        margin-bottom:24px;">
                <h2>🚨 <?= $summary['critical'] ?> Critical Item<?= $summary['critical'] > 1 ? 's' : '' ?></h2>
                <p>
                    These products are at zero stock. Restock immediately or
                    generate a Purchase Order.
                </p>
                <a href="/views/purchase_orders/index.php" class="btn-sm"
                   style="background:#fff; color:#7f1d1d; font-weight:700;">
                    Go to Purchase Orders →
                </a>
            </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="stock-summary">
                <div class="sum-card">
                    <div class="sum-val val-total"><?= $summary['total'] ?></div>
                    <div class="sum-lbl">Total Products</div>
                </div>
                <div class="sum-card">
                    <div class="sum-val val-healthy"><?= $summary['healthy'] ?></div>
                    <div class="sum-lbl">Healthy</div>
                </div>
                <div class="sum-card">
                    <div class="sum-val val-low"><?= $summary['low'] ?></div>
                    <div class="sum-lbl">Low Stock</div>
                </div>
                <div class="sum-card">
                    <div class="sum-val val-critical"><?= $summary['critical'] ?></div>
                    <div class="sum-lbl">Critical</div>
                </div>
            </div>

            <!-- Stock Table -->
            <div class="section-card">
                <div style="display:flex; justify-content:space-between;
                            align-items:center; margin-bottom:16px;">
                    <h3 style="margin-bottom:0;">Stock Levels & Health Status</h3>
                    <a href="/views/products/index.php" class="btn-sm out">
                        + Add Product
                    </a>
                </div>

                <?php if (empty($stockItems)): ?>
                <div class="empty-state" style="min-height:200px;">
                    <div class="empty-icon">📊</div>
                    <h3>No stock records yet</h3>
                    <p>Add products first — stock records are created automatically.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Health</th>
                                <th style="text-align:center;">Current Qty</th>
                                <th style="text-align:center;">Min Level</th>
                                <th>Update Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockItems as $item):
                                $h        = strtolower($item['health_status']);
                                $bdg      = $h === 'healthy' ? 'gd' : ($h === 'low' ? 'lo' : 'cr');
                                $rowClass = $h === 'critical' ? 'health-row-cr'
                                          : ($h === 'low' ? 'health-row-lo' : '');
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <?php if ($item['brand']): ?>
                                    <span style="color:var(--text-muted);
                                                 font-size:.75rem;
                                                 margin-left:6px;">
                                        <?= htmlspecialchars($item['brand']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['category_name'] ?? '—') ?></td>
                                <td><span class="bdg <?= $bdg ?>"><?= $item['health_status'] ?></span></td>
                                <td style="text-align:center; font-size:1.1rem; font-weight:700;
                                           color:<?= $h === 'critical' ? 'var(--danger)' : ($h === 'low' ? 'var(--warning)' : 'var(--accent)') ?>">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td style="text-align:center; color:var(--text-muted);">
                                    <?= $item['min_stock_level'] ?>
                                </td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id"
                                               value="<?= $item['product_id'] ?>">
                                        <label style="font-size:.72rem;
                                                      color:var(--text-muted);">Qty</label>
                                        <input type="number" name="quantity"
                                               class="stock-input"
                                               value="<?= $item['quantity'] ?>"
                                               min="0" required>
                                        <label style="font-size:.72rem;
                                                      color:var(--text-muted);">Min</label>
                                        <input type="number" name="min_stock_level"
                                               class="stock-input min-w"
                                               value="<?= $item['min_stock_level'] ?>"
                                               min="1" required>
                                        <button type="submit" name="update_stock"
                                                class="btn-sm pri">Save</button>
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