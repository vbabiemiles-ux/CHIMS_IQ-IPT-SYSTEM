<?php
global $db;
require_once '../../autoload.php';
requireRole('admin');
$user = currentUser();

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Invalid CSRF token.';
        $msgType = 'error';

    } elseif (isset($_POST['create_backup'])) {
        try {
            $storeId = $user['store_id'];
            $note    = trim($_POST['note'] ?? '');

            // Collect all store data into one snapshot
            $snapshot = [];

            $tables = [
                'categories'  => "SELECT * FROM categories  WHERE store_id = ? AND deleted_at IS NULL",
                'suppliers'   => "SELECT * FROM suppliers   WHERE store_id = ? AND deleted_at IS NULL",
                'products'    => "SELECT * FROM products    WHERE store_id = ? AND deleted_at IS NULL",
                'stock'       => "SELECT s.* FROM stock s
                                  JOIN products p ON s.product_id = p.id
                                  WHERE p.store_id = ?",
                'purchase_orders' => "SELECT * FROM purchase_orders WHERE store_id = ?",
                'purchase_order_items' => "SELECT poi.* FROM purchase_order_items poi
                                           JOIN purchase_orders po ON poi.purchase_order_id = po.id
                                           WHERE po.store_id = ?",
                'stock_flags' => "SELECT sf.* FROM stock_flags sf
                                  JOIN stock s ON sf.stock_id = s.id
                                  JOIN products p ON s.product_id = p.id
                                  WHERE p.store_id = ?",
            ];

            foreach ($tables as $tableName => $sql) {
                $stmt = $db->prepare($sql);
                $stmt->execute([$storeId]);
                $snapshot[$tableName] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $snapshotJson = json_encode([
                'version'    => '3.0',
                'store_id'   => $storeId,
                'store_name' => $user['store_name'],
                'created_at' => date('Y-m-d H:i:s'),
                'tables'     => $snapshot,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare(
                "INSERT INTO backup_snapshots
                 (store_id, triggered_by_user_id, snapshot_data, note)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$storeId, $user['id'], $snapshotJson, $note ?: null]);

            $message = 'Backup created successfully.';
            $msgType = 'success';

        } catch (Throwable $e) {
            $message = 'Backup failed: ' . $e->getMessage();
            $msgType = 'error';
        }

    } elseif (isset($_POST['delete_backup'])) {
        try {
            $stmt = $db->prepare(
                "DELETE FROM backup_snapshots
                 WHERE id = ? AND store_id = ?"
            );
            $stmt->execute([(int)$_POST['backup_id'], $user['store_id']]);
            $message = 'Backup deleted.';
            $msgType = 'success';
        } catch (Throwable $e) {
            $message = 'Delete failed: ' . $e->getMessage();
            $msgType = 'error';
        }
    }
}

// Fetch backups
try {
    $stmt = $db->prepare(
        "SELECT bs.*, u.full_name AS created_by_name
         FROM backup_snapshots bs
         JOIN users u ON bs.triggered_by_user_id = u.id
         WHERE bs.store_id = ?
         ORDER BY bs.created_at DESC"
    );
    $stmt->execute([$user['store_id']]);
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $backups = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backups — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .backup-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            align-items: start;
        }
        .backup-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: start;
            transition: border-color .15s;
        }
        .backup-card:hover { border-color: rgba(0,229,160,.25); }
        .backup-name {
            font-weight: 700;
            font-size: .95rem;
            margin-bottom: 4px;
        }
        .backup-meta {
            font-size: .76rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .backup-tables {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .backup-table-pill {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: .7rem;
            color: var(--text-muted);
        }
        .backup-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        @media(max-width:768px) {
            .backup-grid { grid-template-columns: 1fr; }
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
                <h1>Backups</h1>
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

            <div class="backup-grid">

                <!-- Create Backup Form -->
                <div>
                    <div class="section-card">
                        <h3>💾 Create Backup</h3>
                        <p style="font-size:.83rem; color:var(--text-muted);
                                  margin-bottom:20px; line-height:1.6;">
                            Creates a full JSON snapshot of your store's inventory —
                            categories, suppliers, products, stock levels,
                            purchase orders, and flags.
                        </p>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label class="form-label">Note (optional)</label>
                                <input type="text" name="note" class="form-input"
                                       placeholder="e.g. Before stock update · End of month">
                            </div>
                            <button type="submit" name="create_backup"
                                    class="btn-sm pri"
                                    style="width:100%; padding:12px;
                                           font-size:.95rem;">
                                💾 Create Backup Now
                            </button>
                        </form>
                    </div>

                    <!-- Info Card -->
                    <div class="section-card">
                        <h3>ℹ️ About Backups</h3>
                        <div style="font-size:.82rem; color:var(--text-muted);
                                    line-height:1.7;">
                            <p style="margin-bottom:8px;">
                                Backups capture a point-in-time snapshot of all your
                                store data. They are stored in the database and can be
                                downloaded as JSON.
                            </p>
                            <p style="margin-bottom:8px;">
                                <strong style="color:var(--text-main);">
                                    What is included:
                                </strong><br>
                                Categories · Suppliers · Products · Stock levels ·
                                Purchase Orders · Stock Flags
                            </p>
                            <p>
                                <strong style="color:var(--text-main);">
                                    What is NOT included:
                                </strong><br>
                                User passwords · Deletion log entries
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Backup List -->
                <div class="section-card" style="margin-bottom:0;">
                    <div style="display:flex; justify-content:space-between;
                                align-items:center; margin-bottom:16px;">
                        <h3 style="margin-bottom:0;">
                            Backup History (<?= count($backups) ?>)
                        </h3>
                    </div>

                    <?php if (empty($backups)): ?>
                    <div class="empty-state" style="min-height:200px;">
                        <div class="empty-icon">💾</div>
                        <h3>No backups yet</h3>
                        <p>Create your first backup using the form.</p>
                    </div>

                    <?php else: ?>
                        <?php foreach ($backups as $backup):
                            $data    = json_decode($backup['snapshot_data'], true);
                            $tables  = $data['tables'] ?? [];
                            $rowCounts = [];
                            foreach ($tables as $tbl => $rows) {
                                $rowCounts[] = ucfirst($tbl) . ' (' . count($rows) . ')';
                            }
                            $sizeKb = round(strlen($backup['snapshot_data']) / 1024, 1);
                        ?>
                        <div class="backup-card">
                            <div>
                                <div class="backup-name">
                                    💾
                                    <?= $backup['note']
                                        ? htmlspecialchars($backup['note'])
                                        : 'Backup #' . $backup['id'] ?>
                                </div>
                                <div class="backup-meta">
                                    Created by
                                    <strong>
                                        <?= htmlspecialchars($backup['created_by_name']) ?>
                                    </strong>
                                    &nbsp;·&nbsp;
                                    <?= date('M d, Y · g:i A',
                                             strtotime($backup['created_at'])) ?>
                                    &nbsp;·&nbsp;
                                    <span style="color:var(--accent);">
                                        <?= $sizeKb ?> KB
                                    </span>
                                </div>
                                <div class="backup-tables">
                                    <?php foreach ($rowCounts as $rc): ?>
                                    <span class="backup-table-pill"><?= $rc ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="backup-actions">
                                <!-- Download as JSON -->
                                <button class="btn-sm pri"
                                        onclick="downloadBackup(<?= $backup['id'] ?>,
                                                 `<?= addslashes(
                                                     $backup['note']
                                                     ? preg_replace('/\s+/', '_', $backup['note'])
                                                     : 'backup_' . $backup['id']
                                                 ) ?>`)">
                                    ⬇ Download
                                </button>

                                <form method="POST"
                                      onsubmit="return confirm('Delete this backup? This cannot be undone.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="backup_id"
                                           value="<?= $backup['id'] ?>">
                                    <button type="submit" name="delete_backup"
                                            class="btn-sm red">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Hidden data for download -->
                        <script>
                        window.backupData = window.backupData || {};
                        window.backupData[<?= $backup['id'] ?>] =
                            <?= $backup['snapshot_data'] ?>;
                        </script>

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

function downloadBackup(id, filename) {
    const data   = window.backupData[id];
    if (!data) { alert('Backup data not found.'); return; }
    const json   = JSON.stringify(data, null, 2);
    const blob   = new Blob([json], { type: 'application/json' });
    const url    = URL.createObjectURL(blob);
    const a      = document.createElement('a');
    a.href       = url;
    a.download   = filename + '_' + new Date().toISOString().slice(0,10) + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>