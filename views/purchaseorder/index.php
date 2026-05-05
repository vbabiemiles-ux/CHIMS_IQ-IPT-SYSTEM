<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');

$currentUser = currentUser();
$po          = new PurchaseOrder($db);
$supplierObj = new Supplier($db);
$productObj  = new Product($db);

$message = '';
$msgType = 'success';

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';

    } elseif (isset($_POST['generate_auto'])) {
        $result  = $po->generateAutoDrafts();
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['update_status'])) {
        $result  = $po->updateStatus((int)$_POST['po_id'], $_POST['status']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';

    } elseif (isset($_POST['create_po'])) {
        // Build items array from POST
        $items = [];
        $productIds = $_POST['item_product_id']  ?? [];
        $quantities  = $_POST['item_qty']          ?? [];
        $costs       = $_POST['item_cost']         ?? [];

        foreach ($productIds as $i => $pid) {
            if (!$pid) continue;
            $items[] = [
                'product_id'       => $pid,
                'quantity_ordered'  => $quantities[$i] ?? 1,
                'unit_cost'        => $costs[$i] ?? 0
            ];
        }

        $result  = $po->create(
            $_POST['supplier_id'],
            $_POST['order_date'],
            $_POST['expected_date'] ?: null,
            $items
        );
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$orders    = $po->getAll();
$suppliers = $supplierObj->getAll();
$products  = $productObj->getAll();

$pendingCount   = $po->countByStatus('pending');
$receivedCount  = $po->countByStatus('received');
$cancelledCount = $po->countByStatus('cancelled');

$showForm = isset($_GET['new']) || $msgType === 'error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .po-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .po-stat {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            text-align: center;
        }
        .po-stat-val { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .po-stat-lbl { font-size: .72rem; color: var(--text-muted);
                       text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }

        .po-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 1fr 90px 110px auto;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }
        .item-row input, .item-row select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            color: var(--text-main);
            font-size: .85rem;
            outline: none;
            width: 100%;
        }
        .item-row input:focus,
        .item-row select:focus { border-color: var(--accent); }

        .remove-item {
            background: rgba(248,81,73,.12);
            color: var(--danger);
            border: 1px solid rgba(248,81,73,.2);
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: .8rem;
        }
        .remove-item:hover { background: rgba(248,81,73,.25); }

        .status-select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 5px 8px;
            color: var(--text-main);
            font-size: .8rem;
            outline: none;
            cursor: pointer;
        }
        .status-select:focus { border-color: var(--accent); }

        @media(max-width:768px) {
            .po-stats     { grid-template-columns: 1fr; }
            .po-form-grid { grid-template-columns: 1fr; }
            .item-row     { grid-template-columns: 1fr 1fr; }
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
                <h1>Purchase Orders</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($currentUser['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <?php if ($message): ?>
            <div class="alert-msg <?= $msgType ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- PO Stats -->
            <div class="po-stats">
                <div class="po-stat">
                    <div class="po-stat-val" style="color:var(--warning);">
                        <?= $pendingCount ?>
                    </div>
                    <div class="po-stat-lbl">Pending</div>
                </div>
                <div class="po-stat">
                    <div class="po-stat-val" style="color:var(--accent);">
                        <?= $receivedCount ?>
                    </div>
                    <div class="po-stat-lbl">Received</div>
                </div>
                <div class="po-stat">
                    <div class="po-stat-val" style="color:var(--text-muted);">
                        <?= $cancelledCount ?>
                    </div>
                    <div class="po-stat-lbl">Cancelled</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
                <a href="?new=1" class="btn-sm pri">+ New Purchase Order</a>
                <form method="POST" style="display:inline;"
                      onsubmit="return confirm('Auto-generate PO drafts for all Critical stock?')">
                    <?= csrf_field() ?>
                    <button type="submit" name="generate_auto" class="btn-sm out">
                        🚨 Auto-Draft from Critical Stock
                    </button>
                </form>
            </div>

            <!-- Manual PO Creation Form -->
            <?php if ($showForm): ?>
            <div class="section-card" style="margin-bottom:24px;">
                <h3>➕ Create Purchase Order</h3>
                <form method="POST" id="poForm">
                    <?= csrf_field() ?>

                    <div class="po-form-grid" style="margin-bottom:16px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Supplier *</label>
                            <select name="supplier_id" class="form-input" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['supplier_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Order Date *</label>
                            <input type="date" name="order_date" class="form-input"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_date" class="form-input">
                        </div>
                    </div>

                    <!-- Items -->
                    <div style="margin-bottom:12px;">
                        <label class="form-label">Order Items *</label>
                        <div style="display:grid;
                                    grid-template-columns:1fr 90px 110px auto;
                                    gap:8px; margin-bottom:8px;">
                            <span style="font-size:.72rem; color:var(--text-muted);
                                         text-transform:uppercase; letter-spacing:1px;">
                                Product
                            </span>
                            <span style="font-size:.72rem; color:var(--text-muted);
                                         text-transform:uppercase; letter-spacing:1px;">
                                Qty
                            </span>
                            <span style="font-size:.72rem; color:var(--text-muted);
                                         text-transform:uppercase; letter-spacing:1px;">
                                Unit Cost (₱)
                            </span>
                            <span></span>
                        </div>
                        <div id="itemsContainer">
                            <div class="item-row">
                                <select name="item_product_id[]" required>
                                    <option value="">Select product…</option>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['product_name']) ?>
                                        <?= $p['brand'] ? '('.$p['brand'].')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="item_qty[]"
                                       min="1" value="1" required>
                                <input type="number" name="item_cost[]"
                                       step="0.01" min="0" value="0.00" required>
                                <button type="button" class="remove-item"
                                        onclick="removeItem(this)">✕</button>
                            </div>
                        </div>
                        <button type="button" class="btn-sm out"
                                style="margin-top:10px;"
                                onclick="addItem()">+ Add Item</button>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:16px;">
                        <button type="submit" name="create_po"
                                class="btn-sm pri">Create PO</button>
                        <a href="<?= BASE_URL ?>views/purchaseorder/index.php"
                           class="btn-sm out">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- PO Table -->
            <div class="section-card">
                <h3>All Purchase Orders (<?= count($orders) ?>)</h3>

                <?php if (empty($orders)): ?>
                <div class="empty-state" style="min-height:200px;">
                    <div class="empty-icon">🛒</div>
                    <h3>No purchase orders yet</h3>
                    <p>Create one manually or auto-generate from critical stock.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Supplier</th>
                                <th>Created By</th>
                                <th>Order Date</th>
                                <th>Items</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order):
                            $st  = $order['status'];
                            $bdg = $st === 'received' ? 'gd'
                                 : ($st === 'cancelled' ? 'cr' : 'lo');
                        ?>
                        <tr>
                            <td>
                                <strong style="font-family:monospace;">
                                    #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>
                                </strong>
                            </td>
                            <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($order['created_by']) ?></td>
                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td style="text-align:center;">
                                <?= $order['item_count'] ?>
                            </td>
                            <td>
                                <?php if ($order['total_cost'] > 0): ?>
                                    ₱<?= number_format($order['total_cost'], 2) ?>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">TBD</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="bdg <?= $bdg ?>"><?= ucfirst($st) ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>views/purchaseorder/view.php?id=<?= $order['id'] ?>"
                                   class="btn-sm out">View</a>

                                <?php if ($st === 'pending'): ?>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Mark this PO as received? Stock will be updated automatically.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="po_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="received">
                                    <button type="submit" name="update_status"
                                            class="btn-sm pri">Mark Received</button>
                                </form>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Cancel this PO?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="po_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" name="update_status"
                                            class="btn-sm red">Cancel</button>
                                </form>
                                <?php endif; ?>
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

// Cache the product options HTML once
const productOptions = `<?php
    $opts = '<option value="">Select product…</option>';
    foreach ($products as $p) {
        $label = htmlspecialchars($p['product_name']);
        if ($p['brand']) $label .= ' (' . htmlspecialchars($p['brand']) . ')';
        $opts .= "<option value=\"{$p['id']}\">$label</option>";
    }
    echo addslashes($opts);
?>`;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
        <select name="item_product_id[]" required>
            ${productOptions}
        </select>
        <input type="number" name="item_qty[]" min="1" value="1" required>
        <input type="number" name="item_cost[]" step="0.01" min="0"
               value="0.00" required>
        <button type="button" class="remove-item"
                onclick="removeItem(this)">✕</button>
    `;
    container.appendChild(row);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length === 1) {
        alert('A purchase order needs at least one item.');
        return;
    }
    btn.closest('.item-row').remove();
}
</script>
</body>
</html>