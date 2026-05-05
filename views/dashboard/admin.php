<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');

$user    = currentUser();
$stock   = new Stock($db);
$flagObj = new StockFlag($db);
$po      = new PurchaseOrder($db);

// Live stats
$summary        = $stock->getHealthSummary();
$criticalItems  = $stock->getCritical();
$recentFlags    = $flagObj->getAll();
$pendingPOs     = $po->countByStatus('pending');
$allOrders      = $po->getAll();
$recentOrders   = array_slice($allOrders, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .dash-grid .full-width { grid-column: 1 / -1; }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .flag-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            gap: 12px;
        }
        .flag-row:last-child { border-bottom: none; }
        .flag-product { font-weight: 600; font-size: .88rem; margin-bottom: 3px; }
        .flag-by      { font-size: .76rem; color: var(--text-muted); }
        .flag-note-sm {
            font-size: .78rem;
            color: var(--text-muted);
            font-style: italic;
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 280px;
        }

        .critical-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: .88rem;
        }
        .critical-row:last-child { border-bottom: none; }
        .cr-name { font-weight: 600; }
        .cr-qty  {
            font-size: 1rem;
            font-weight: 800;
            color: var(--danger);
        }

        .health-bar-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: .83rem;
        }
        .health-bar-label { width: 60px; color: var(--text-muted); }
        .health-bar-track {
            flex: 1;
            height: 8px;
            background: var(--bg-input);
            border-radius: 99px;
            overflow: hidden;
        }
        .health-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .4s ease;
        }
        .health-bar-count { width: 28px; text-align: right; font-weight: 700; }

        @media(max-width:900px) {
            .dash-grid { grid-template-columns: 1fr; }
            .dash-grid .full-width { grid-column: 1; }
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
                <h1>Dashboard</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <!-- Welcome Banner -->
            <div class="w-banner" style="margin-bottom:24px;">
                <h2>Welcome back, <?= htmlspecialchars($user['full_name']) ?>! 👋</h2>
                <p>
                    <?= htmlspecialchars($user['store_name']) ?> &nbsp;·&nbsp;
                    <?= date('l, F j, Y') ?>
                </p>
            </div>

            <!-- Critical Banner -->
            <?php if (!empty($criticalItems)): ?>
            <div class="alert-banner"
                 style="background:linear-gradient(135deg,#7f1d1d,#dc2626);
                        margin-bottom:24px; display:flex;
                        justify-content:space-between; align-items:center;
                        flex-wrap:wrap; gap:12px;">
                <div>
                    <h2 style="margin-bottom:4px;">
                        🚨 <?= count($criticalItems) ?> Critical Stock
                        Item<?= count($criticalItems) > 1 ? 's' : '' ?>
                    </h2>
                    <p style="margin-bottom:0;">
                        These products are at zero — immediate restocking required.
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>views/stocks/index.php"
                       class="btn-sm"
                       style="background:rgba(255,255,255,.2);
                              color:#fff; border:1px solid rgba(255,255,255,.3);">
                        View Stock
                    </a>
                    <form method="POST"
                          action="<?= BASE_URL ?>views/purchaseorder/index.php">
                        <?= csrf_field() ?>
                        <button type="submit" name="generate_auto"
                                class="btn-sm"
                                style="background:#fff; color:#7f1d1d; font-weight:700;"
                                onclick="return confirm('Auto-generate PO drafts for critical stock?')">
                            Auto-Draft POs
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="stats" style="margin-bottom:24px;">
                <div class="scard">
                    <div class="sc-hdr">
                        <div class="sc-ico"
                             style="background:rgba(0,229,160,.1);">📦</div>
                    </div>
                    <div class="sc-val"><?= $summary['total'] ?></div>
                    <div class="sc-lbl">Total Products</div>
                </div>
                <div class="scard">
                    <div class="sc-hdr">
                        <div class="sc-ico"
                             style="background:rgba(248,81,73,.1);">🔴</div>
                    </div>
                    <div class="sc-val"
                         style="color:var(--danger);">
                        <?= $summary['critical'] ?>
                    </div>
                    <div class="sc-lbl">Critical Stock</div>
                </div>
                <div class="scard">
                    <div class="sc-hdr">
                        <div class="sc-ico"
                             style="background:rgba(227,179,65,.1);">🚩</div>
                    </div>
                    <div class="sc-val"
                         style="color:var(--warning);">
                        <?= count($recentFlags) ?>
                    </div>
                    <div class="sc-lbl">Open Flags</div>
                </div>
                <div class="scard">
                    <div class="sc-hdr">
                        <div class="sc-ico"
                             style="background:rgba(0,229,160,.1);">🛒</div>
                    </div>
                    <div class="sc-val"
                         style="color:var(--accent);">
                        <?= $pendingPOs ?>
                    </div>
                    <div class="sc-lbl">Pending POs</div>
                </div>
            </div>

            <!-- Main Dashboard Grid -->
            <div class="dash-grid">

                <!-- LEFT COL: Stock Health + Critical Items -->
                <div>
                    <!-- Health Breakdown -->
                    <div class="section-card">
                        <div style="display:flex; justify-content:space-between;
                                    align-items:center; margin-bottom:16px;">
                            <h3 style="margin-bottom:0;">📊 Stock Health</h3>
                            <a href="<?= BASE_URL ?>views/stocks/index.php"
                               class="btn-sm out"
                               style="font-size:.75rem;">View All →</a>
                        </div>

                        <?php
                        $total = max(1, (int)$summary['total']);

                        $bars = [
                            ['label' => 'Healthy', 'count' => $summary['healthy'],
                             'color' => 'var(--accent)'],
                            ['label' => 'Low',     'count' => $summary['low'],
                             'color' => 'var(--warning)'],
                            ['label' => 'Critical','count' => $summary['critical'],
                             'color' => 'var(--danger)'],
                        ];
                        foreach ($bars as $bar):
                            $pct = round(($bar['count'] / $total) * 100);
                        ?>
                        <div class="health-bar-wrap">
                            <span class="health-bar-label"><?= $bar['label'] ?></span>
                            <div class="health-bar-track">
                                <div class="health-bar-fill"
                                     style="width:<?= $pct ?>%;
                                            background:<?= $bar['color'] ?>;"></div>
                            </div>
                            <span class="health-bar-count"
                                  style="color:<?= $bar['color'] ?>">
                                <?= $bar['count'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Critical Items List -->
                    <?php if (!empty($criticalItems)): ?>
                    <div class="section-card">
                        <div style="display:flex; justify-content:space-between;
                                    align-items:center; margin-bottom:16px;">
                            <h3 style="margin-bottom:0;">🔴 Critical Items</h3>
                            <a href="<?= BASE_URL ?>views/stocks/index.php"
                               class="btn-sm out"
                               style="font-size:.75rem;">Manage →</a>
                        </div>
                        <?php foreach (array_slice($criticalItems, 0, 6) as $item): ?>
                        <div class="critical-row">
                            <div>
                                <div class="cr-name">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </div>
                                <div style="font-size:.75rem; color:var(--text-muted);">
                                    Min level: <?= $item['min_stock_level'] ?>
                                </div>
                            </div>
                            <span class="cr-qty">0</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($criticalItems) > 6): ?>
                        <div style="text-align:center; margin-top:12px;">
                            <a href="<?= BASE_URL ?>views/stocks/index.php"
                               style="font-size:.8rem; color:var(--accent);">
                                +<?= count($criticalItems) - 6 ?> more →
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="section-card">
                        <h3>⚡ Quick Actions</h3>
                        <div class="quick-actions">
                            <a href="<?= BASE_URL ?>views/products/index.php?new=1"
                               class="btn-sm pri">+ Add Product</a>
                            <a href="<?= BASE_URL ?>views/stocks/index.php"
                               class="btn-sm out">Update Stock</a>
                            <a href="<?= BASE_URL ?>views/purchaseorder/index.php?new=1"
                               class="btn-sm out">+ New PO</a>
                            <a href="<?= BASE_URL ?>views/categories/index.php"
                               class="btn-sm out">Categories</a>
                            <a href="<?= BASE_URL ?>views/suppliers/index.php"
                               class="btn-sm out">Suppliers</a>
                            <a href="<?= BASE_URL ?>views/stocks/flag.php"
                               class="btn-sm out">View Flags</a>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COL: Flags + Recent POs -->
                <div>

                    <!-- Staff Flags -->
                    <div class="section-card">
                        <div style="display:flex; justify-content:space-between;
                                    align-items:center; margin-bottom:16px;">
                            <h3 style="margin-bottom:0;">
                                🚩 Staff Flags
                                <?php if (!empty($recentFlags)): ?>
                                <span class="nav-badge"
                                      style="font-size:.7rem; padding:2px 8px;
                                             margin-left:6px;">
                                    <?= count($recentFlags) ?>
                                </span>
                                <?php endif; ?>
                            </h3>
                            <a href="<?= BASE_URL ?>views/stocks/flag.php"
                               class="btn-sm out"
                               style="font-size:.75rem;">View All →</a>
                        </div>

                        <?php if (empty($recentFlags)): ?>
                        <div style="text-align:center; padding:24px 0;
                                    color:var(--text-muted); font-size:.85rem;">
                            <div style="font-size:2rem; margin-bottom:8px;
                                        opacity:.4;">🚩</div>
                            No open flags — all clear.
                        </div>
                        <?php else: ?>
                            <?php foreach (array_slice($recentFlags, 0, 4) as $f):
                                $h   = strtolower($f['health_status']);
                                $bdg = $h === 'healthy' ? 'gd'
                                     : ($h === 'low' ? 'lo' : 'cr');
                            ?>
                            <div class="flag-row">
                                <div style="flex:1; min-width:0;">
                                    <div class="flag-product">
                                        <?= htmlspecialchars($f['product_name']) ?>
                                        <span class="bdg <?= $bdg ?>"
                                              style="margin-left:6px;">
                                            <?= $f['health_status'] ?>
                                        </span>
                                    </div>
                                    <div class="flag-by">
                                        By <?= htmlspecialchars($f['flagged_by']) ?>
                                        · <?= date('M d', strtotime($f['flagged_at'])) ?>
                                    </div>
                                    <?php if ($f['note']): ?>
                                    <div class="flag-note-sm">
                                        "<?= htmlspecialchars($f['note']) ?>"
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <span class="bdg lo"
                                      style="white-space:nowrap; flex-shrink:0;">
                                    Pending
                                </span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($recentFlags) > 4): ?>
                            <div style="text-align:center; margin-top:12px;">
                                <a href="<?= BASE_URL ?>views/stocks/flag.php"
                                   style="font-size:.8rem; color:var(--accent);">
                                    +<?= count($recentFlags) - 4 ?> more flags →
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Purchase Orders -->
                    <div class="section-card">
                        <div style="display:flex; justify-content:space-between;
                                    align-items:center; margin-bottom:16px;">
                            <h3 style="margin-bottom:0;">🛒 Recent POs</h3>
                            <a href="<?= BASE_URL ?>views/purchaseorder/index.php"
                               class="btn-sm out"
                               style="font-size:.75rem;">View All →</a>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                        <div style="text-align:center; padding:24px 0;
                                    color:var(--text-muted); font-size:.85rem;">
                            <div style="font-size:2rem; margin-bottom:8px;
                                        opacity:.4;">🛒</div>
                            No purchase orders yet.
                        </div>
                        <?php else: ?>
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>PO #</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentOrders as $order):
                                    $st  = $order['status'];
                                    $bdg = $st === 'received' ? 'gd'
                                         : ($st === 'cancelled' ? 'cr' : 'lo');
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>views/purchaseorder/view.php?id=<?= $order['id'] ?>"
                                           style="color:var(--accent);
                                                  font-family:monospace;
                                                  font-size:.85rem;">
                                            #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td style="font-size:.83rem;">
                                        <?= htmlspecialchars($order['supplier_name']) ?>
                                    </td>
                                    <td>
                                        <span class="bdg <?= $bdg ?>">
                                            <?= ucfirst($st) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.8rem;
                                               color:var(--text-muted);">
                                        <?= date('M d', strtotime($order['order_date'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                </div><!-- end right col -->
            </div><!-- end dash-grid -->
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