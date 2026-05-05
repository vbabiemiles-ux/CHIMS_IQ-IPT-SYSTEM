<?php
global $db;
require_once '../../autoload.php';
requireRole('staff');

$user    = currentUser();
$flagObj = new StockFlag($db);
$stock   = new Stock($db);
$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';
    } elseif (isset($_POST['submit_flag'])) {
        $result  = $flagObj->create((int)$_POST['stock_id'], $_POST['note'] ?? '');
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$stockItems = $stock->getAllWithHealth();
$myFlags    = $flagObj->getMyFlags();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flag Items — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .flag-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
            align-items: start;
        }
        .my-flag-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-size: .85rem;
        }
        .my-flag-row:last-child { border-bottom: none; }
        .mf-product { font-weight: 600; margin-bottom: 2px; }
        .mf-date    { font-size: .75rem; color: var(--text-muted); }
        @media(max-width:768px) {
            .flag-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_staff.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Flag Stock Item</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span style="color:var(--text-muted);">staff</span>
            </div>
        </div>

        <div class="page-body">

            <?php if ($message): ?>
            <div class="alert-msg <?= $msgType ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="flag-layout">

                <!-- Flag Form -->
                <div class="section-card" style="align-self:start;">
                    <h3>🚩 Report a Stock Issue</h3>
                    <p style="font-size:.83rem; color:var(--text-muted); margin-bottom:20px;">
                        Select the product with the issue and describe what you observed.
                        Your admin will be notified.
                    </p>
                    <form method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label class="form-label">Select Product *</label>
                            <select name="stock_id" class="form-input" required>
                                <option value="">Choose a product…</option>
                                <?php foreach ($stockItems as $item):
                                    $h   = strtolower($item['health_status']);
                                    $lbl = $h === 'critical' ? '🔴' : ($h === 'low' ? '🟡' : '🟢');
                                ?>
                                <option value="<?= $item['id'] ?>">
                                    <?= $lbl ?> <?= htmlspecialchars($item['product_name']) ?>
                                    (Qty: <?= $item['quantity'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Note / Observation *</label>
                            <textarea name="note" class="form-input" rows="4"
                                      placeholder="Describe the issue you observed…"
                                      required></textarea>
                        </div>

                        <button type="submit" name="submit_flag" class="btn-sm pri"
                                style="width:100%; padding:12px; font-size:.95rem;">
                            Submit Flag
                        </button>
                    </form>
                </div>

                <!-- My Flags History -->
                <div class="section-card">
                    <h3>📋 My Submitted Flags (<?= count($myFlags) ?>)</h3>

                    <?php if (empty($myFlags)): ?>
                    <div class="empty-state" style="min-height:200px;">
                        <div class="empty-icon">🚩</div>
                        <h3>No flags yet</h3>
                        <p>Your submitted flags will appear here.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($myFlags as $f):
                            $h   = strtolower($f['health_status']);
                            $bdg = $h === 'healthy' ? 'gd' : ($h === 'low' ? 'lo' : 'cr');
                        ?>
                        <div class="my-flag-row">
                            <div>
                                <div class="mf-product">
                                    <?= htmlspecialchars($f['product_name']) ?>
                                    <span class="bdg <?= $bdg ?>"
                                          style="margin-left:6px;">
                                        <?= $f['health_status'] ?>
                                    </span>
                                </div>
                                <?php if ($f['note']): ?>
                                <div style="color:var(--text-muted); font-size:.8rem;
                                            margin-top:3px; font-style:italic;">
                                    "<?= htmlspecialchars($f['note']) ?>"
                                </div>
                                <?php endif; ?>
                                <div class="mf-date">
                                    <?= date('M d, Y · g:i A', strtotime($f['flagged_at'])) ?>
                                </div>
                            </div>
                            <span class="bdg lo" style="white-space:nowrap;">Pending</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

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