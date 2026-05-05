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
    } elseif (isset($_POST['restore_record'])) {
        $result  = restoreFromDeletionLog((int)$_POST['log_id']);
        $message = $result['message'];
        $msgType = $result['status'] ? 'success' : 'error';
    } elseif (isset($_POST['purge_record'])) {
        // Hard delete from deletion_log only (not restore)
        try {
            $stmt = $db->prepare(
                "DELETE FROM deletion_log
                 WHERE id = ? AND store_id = ?"
            );
            $stmt->execute([(int)$_POST['log_id'], $user['store_id']]);
            $message = 'Record permanently purged from log.';
            $msgType = 'success';
        } catch (Throwable $e) {
            $message = 'Purge failed: ' . $e->getMessage();
            $msgType = 'error';
        }
    }
}

// Filters
$filterTable   = $_GET['table']   ?? '';
$filterStatus  = $_GET['status']  ?? '';

// Build query
$where   = ["dl.store_id = ?"];
$params  = [$user['store_id']];

if ($filterTable) {
    $where[]  = "dl.table_name = ?";
    $params[] = $filterTable;
}
if ($filterStatus === 'restored') {
    $where[] = "dl.is_restored = 1";
} elseif ($filterStatus === 'pending') {
    $where[] = "dl.is_restored = 0 AND dl.expires_at > NOW()";
} elseif ($filterStatus === 'expired') {
    $where[] = "dl.is_restored = 0 AND dl.expires_at <= NOW()";
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $db->prepare(
        "SELECT dl.*,
                u.full_name AS deleted_by_name
         FROM deletion_log dl
         JOIN users u ON dl.deleted_by_user_id = u.id
         WHERE $whereClause
         ORDER BY dl.deleted_at DESC"
    );
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $logs = [];
    $message = 'Query error: ' . $e->getMessage();
    $msgType = 'error';
}

// Count by table for filter pills
try {
    $countStmt = $db->prepare(
        "SELECT table_name, COUNT(*) as cnt
         FROM deletion_log
         WHERE store_id = ? AND is_restored = 0 AND expires_at > NOW()
         GROUP BY table_name"
    );
    $countStmt->execute([$user['store_id']]);
    $tableCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $e) {
    $tableCounts = [];
}

$tableLabels = [
    'products'   => '📦 Products',
    'categories' => '🏷️ Categories',
    'suppliers'  => '🏭 Suppliers',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deletion Log — CHIMS-IQ</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            align-items: center;
        }
        .filter-pill {
            padding: 6px 16px;
            border-radius: 99px;
            font-size: .78rem;
            font-weight: 600;
            border: 1px solid var(--border);
            color: var(--text-muted);
            background: var(--bg-card);
            text-decoration: none;
            transition: all .15s;
        }
        .filter-pill:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        .filter-pill.active {
            background: var(--accent);
            color: #0d1117;
            border-color: var(--accent);
        }
        .log-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: start;
            transition: border-color .15s;
        }
        .log-card:hover { border-color: rgba(0,229,160,.25); }
        .log-card.restored {
            opacity: .55;
            border-style: dashed;
        }
        .log-card.expired {
            border-color: rgba(248,81,73,.25);
        }
        .log-table-icon {
            width: 40px; height: 40px;
            background: var(--bg-input);
            border-radius: 10px;
            display: flex; align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .log-record-name {
            font-weight: 700;
            font-size: .95rem;
            margin-bottom: 4px;
        }
        .log-meta {
            font-size: .76rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .log-snapshot {
            font-size: .73rem;
            color: var(--text-muted);
            font-family: monospace;
            background: var(--bg-input);
            border-radius: 6px;
            padding: 6px 10px;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            transition: max-height .3s;
        }
        .log-snapshot.expanded {
            max-height: 300px;
            white-space: pre-wrap;
            overflow-y: auto;
        }
        .log-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            flex-shrink: 0;
        }
        .expiry-bar {
            height: 4px;
            background: var(--bg-input);
            border-radius: 99px;
            margin-top: 8px;
            overflow: hidden;
        }
        .expiry-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .3s;
        }

        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .sum-mini {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
            text-align: center;
        }
        .sum-mini-val {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .sum-mini-lbl {
            font-size: .7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        @media(max-width:768px) {
            .log-card { grid-template-columns: auto 1fr; }
            .log-actions { flex-direction: row; grid-column: 1 / -1; }
            .summary-row { grid-template-columns: repeat(2, 1fr); }
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
                <h1>Deletion Log</h1>
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

            <!-- Summary -->
            <?php
            try {
                $totStmt = $db->prepare(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(is_restored = 0 AND expires_at > NOW())  AS restorable,
                        SUM(is_restored = 1)                          AS restored,
                        SUM(is_restored = 0 AND expires_at <= NOW())  AS expired
                     FROM deletion_log WHERE store_id = ?"
                );
                $totStmt->execute([$user['store_id']]);
                $totals = $totStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $totals = ['total'=>0,'restorable'=>0,'restored'=>0,'expired'=>0];
            }
            ?>
            <div class="summary-row">
                <div class="sum-mini">
                    <div class="sum-mini-val"><?= $totals['total'] ?></div>
                    <div class="sum-mini-lbl">Total Logged</div>
                </div>
                <div class="sum-mini">
                    <div class="sum-mini-val"
                         style="color:var(--accent);"><?= $totals['restorable'] ?></div>
                    <div class="sum-mini-lbl">Restorable</div>
                </div>
                <div class="sum-mini">
                    <div class="sum-mini-val"
                         style="color:var(--text-muted);"><?= $totals['restored'] ?></div>
                    <div class="sum-mini-lbl">Restored</div>
                </div>
                <div class="sum-mini">
                    <div class="sum-mini-val"
                         style="color:var(--danger);"><?= $totals['expired'] ?></div>
                    <div class="sum-mini-lbl">Expired</div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <span style="font-size:.75rem;
                             color:var(--text-muted);
                             text-transform:uppercase;
                             letter-spacing:1px;">
                    Filter:
                </span>
                <a href="?" class="filter-pill <?= !$filterTable && !$filterStatus ? 'active' : '' ?>">
                    All
                </a>
                <?php foreach ($tableLabels as $tbl => $lbl): ?>
                <a href="?table=<?= $tbl ?>"
                   class="filter-pill <?= $filterTable === $tbl ? 'active' : '' ?>">
                    <?= $lbl ?>
                    <?php if (!empty($tableCounts[$tbl])): ?>
                    <span style="margin-left:4px; opacity:.7;">
                        (<?= $tableCounts[$tbl] ?>)
                    </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <div style="width:1px; height:20px;
                            background:var(--border); margin:0 4px;"></div>
                <a href="?status=pending"
                   class="filter-pill <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                    Restorable
                </a>
                <a href="?status=restored"
                   class="filter-pill <?= $filterStatus === 'restored' ? 'active' : '' ?>">
                    Restored
                </a>
                <a href="?status=expired"
                   class="filter-pill <?= $filterStatus === 'expired' ? 'active' : '' ?>">
                    Expired
                </a>
            </div>

            <!-- Log Cards -->
            <?php if (empty($logs)): ?>
            <div class="empty-state" style="min-height:300px;">
                <div class="empty-icon">🗑️</div>
                <h3>No deletion records</h3>
                <p>Soft-deleted items will appear here for 30 days
                   before permanent removal.</p>
            </div>

            <?php else: ?>
                <?php foreach ($logs as $log):
                    $isRestored = (bool)$log['is_restored'];
                    $isExpired  = !$isRestored && strtotime($log['expires_at']) <= time();
                    $snapshot   = json_decode($log['snapshot_data'], true) ?? [];

                    // Get a human-readable record name from snapshot
                    $recordName = $snapshot['product_name']
                        ?? $snapshot['category_name']
                        ?? $snapshot['supplier_name']
                        ?? ('Record #' . $log['record_id']);

                    // Days remaining
                    $daysLeft   = max(0, ceil((strtotime($log['expires_at']) - time()) / 86400));
                    $expiryPct  = min(100, round(($daysLeft / 30) * 100));
                    $expiryColor= $daysLeft <= 3
                        ? 'var(--danger)'
                        : ($daysLeft <= 7 ? 'var(--warning)' : 'var(--accent)');

                    $tableIcon  = [
                        'products'   => '📦',
                        'categories' => '🏷️',
                        'suppliers'  => '🏭',
                    ][$log['table_name']] ?? '🗃️';

                    $cardClass = $isRestored ? 'restored' : ($isExpired ? 'expired' : '');
                ?>
                <div class="log-card <?= $cardClass ?>">

                    <!-- Icon -->
                    <div class="log-table-icon"><?= $tableIcon ?></div>

                    <!-- Info -->
                    <div style="min-width:0;">
                        <div class="log-record-name">
                            <?= htmlspecialchars($recordName) ?>
                            <?php if ($isRestored): ?>
                            <span class="bdg gd" style="margin-left:8px;">Restored</span>
                            <?php elseif ($isExpired): ?>
                            <span class="bdg cr" style="margin-left:8px;">Expired</span>
                            <?php else: ?>
                            <span class="bdg lo" style="margin-left:8px;">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="log-meta">
                            <strong><?= ucfirst($log['table_name']) ?></strong>
                            &nbsp;·&nbsp; Deleted by
                            <strong><?= htmlspecialchars($log['deleted_by_name']) ?></strong>
                            &nbsp;·&nbsp;
                            <?= date('M d, Y · g:i A', strtotime($log['deleted_at'])) ?>
                            <?php if ($isRestored && $log['restored_at']): ?>
                            &nbsp;·&nbsp; Restored:
                            <?= date('M d, Y', strtotime($log['restored_at'])) ?>
                            <?php endif; ?>
                        </div>

                        <!-- Snapshot preview -->
                        <div class="log-snapshot"
                             id="snap-<?= $log['id'] ?>"
                             onclick="toggleSnap(<?= $log['id'] ?>)"
                             title="Click to expand">
                            <?= htmlspecialchars(json_encode($snapshot, JSON_PRETTY_PRINT)) ?>
                        </div>

                        <!-- Expiry bar -->
                        <?php if (!$isRestored && !$isExpired): ?>
                        <div style="margin-top:8px; display:flex;
                                    align-items:center; gap:8px;">
                            <div class="expiry-bar" style="flex:1;">
                                <div class="expiry-fill"
                                     style="width:<?= $expiryPct ?>%;
                                            background:<?= $expiryColor ?>;"></div>
                            </div>
                            <span style="font-size:.72rem;
                                         color:<?= $expiryColor ?>;
                                         white-space:nowrap;">
                                <?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?> left
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="log-actions">
                        <?php if (!$isRestored && !$isExpired): ?>
                        <form method="POST"
                              onsubmit="return confirm('Restore this record?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <button type="submit" name="restore_record"
                                    class="btn-sm pri">
                                ↩ Restore
                            </button>
                        </form>
                        <?php endif; ?>

                        <form method="POST"
                              onsubmit="return confirm('Permanently purge this log entry? This cannot be undone.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <button type="submit" name="purge_record"
                                    class="btn-sm red">
                                🗑 Purge
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
function toggleSnap(id) {
    const el = document.getElementById('snap-' + id);
    el.classList.toggle('expanded');
}
</script>
</body>
</html>