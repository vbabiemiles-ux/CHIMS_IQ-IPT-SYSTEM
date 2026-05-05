<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');

$user    = currentUser();
$flagObj = new StockFlag($db);
$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';
    } elseif (isset($_POST['resolve_flag'])) {
        $result  = $flagObj->resolve((int)$_POST['flag_id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    }
}

$flags = $flagObj->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Flags — CHIMS-IQ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .flag-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 14px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: start;
            transition: border-color .2s;
        }
        .flag-card:hover { border-color: rgba(227,179,65,.4); }
        .flag-product {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .flag-meta {
            font-size: .78rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .flag-note {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: .85rem;
            color: var(--text-main);
            line-height: 1.5;
        }
        .flag-note::before {
            content: '💬 ';
        }
        .flag-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Staff Flags</h1>
            </div>
            <div class="topbar-right">
                <?= htmlspecialchars($user['full_name']) ?> &nbsp;·&nbsp;
                <span class="rbdg admin">ADMIN</span>
            </div>
        </div>

        <div class="page-body">

            <?php if ($message): ?>
            <div class="alert-msg <?= $msgType ?>" style="margin-bottom:20px;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between;
                        align-items:center; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:1.1rem; font-weight:700;">
                        Unresolved Flags
                        <?php if (!empty($flags)): ?>
                        <span class="nav-badge" style="font-size:.75rem; padding:3px 10px;
                              border-radius:20px; margin-left:8px;">
                            <?= count($flags) ?>
                        </span>
                        <?php endif; ?>
                    </h2>
                    <p style="color:var(--text-muted); font-size:.83rem; margin-top:4px;">
                        Staff-reported stock issues that need your attention.
                    </p>
                </div>
                <a href="<?= BASE_URL ?>views/stocks/index.php" class="btn-sm out">← Stock</a>
            </div>

            <?php if (empty($flags)): ?>
            <div class="empty-state" style="min-height:300px;">
                <div class="empty-icon">🚩</div>
                <h3>No open flags</h3>
                <p>All clear — staff haven't reported any stock issues.</p>
            </div>

            <?php else: ?>
                <?php foreach ($flags as $f):
                    $h   = strtolower($f['health_status']);
                    $bdg = $h === 'healthy' ? 'gd' : ($h === 'low' ? 'lo' : 'cr');
                ?>
                <div class="flag-card">
                    <div>
                        <div class="flag-product">
                            📦 <?= htmlspecialchars($f['product_name']) ?>
                            &nbsp;
                            <span class="bdg <?= $bdg ?>"><?= $f['health_status'] ?></span>
                            <span style="color:var(--text-muted); font-size:.8rem; margin-left:8px;">
                                Qty: <?= $f['quantity'] ?>
                            </span>
                        </div>
                        <div class="flag-meta">
                            🚩 Flagged by <strong><?= htmlspecialchars($f['flagged_by']) ?></strong>
                            &nbsp;·&nbsp;
                            <?= date('M d, Y · g:i A', strtotime($f['flagged_at'])) ?>
                        </div>
                        <?php if ($f['note']): ?>
                        <div class="flag-note">
                            <?= htmlspecialchars($f['note']) ?>
                        </div>
                        <?php else: ?>
                        <div class="flag-note" style="color:var(--text-muted); font-style:italic;">
                            No note provided.
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flag-actions">
                        <a href="<?= BASE_URL ?>views/stocks/index.php" class="btn-sm out">
                            Update Stock
                        </a>
                        <form method="POST"
                              onsubmit="return confirm('Mark this flag as resolved?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="flag_id" value="<?= $f['id'] ?>">
                            <button type="submit" name="resolve_flag"
                                    class="btn-sm pri">
                                ✓ Resolve
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

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