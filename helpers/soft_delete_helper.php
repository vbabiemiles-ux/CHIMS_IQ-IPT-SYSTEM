<?php

/**
 * CHIMS-IQ - Soft Delete Helper
 * Centralised soft delete with DELETION_LOG snapshot
 */

function softDeleteRecord($tableName, $recordId, $deletedByUserId) {
    global $db;

    try {
        $storeId = $_SESSION['store_id'] ?? null;
        if (!$storeId && ($_SESSION['role'] ?? '') !== 'superadmin') {
            return ['status' => false, 'message' => 'Store context required for soft delete'];
        }

        // Fetch full record before soft delete
        $stmt = $db->prepare("SELECT * FROM `$tableName` WHERE id = ? LIMIT 1");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return ['status' => false, 'message' => 'Record not found'];
        }

        // Log deletion with full snapshot
        $snapshot = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare(
            "INSERT INTO deletion_log 
             (table_name, record_id, snapshot_data, deleted_by_user_id, store_id, expires_at) 
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))"
        );
        $stmt->execute([$tableName, $recordId, $snapshot, $deletedByUserId, $storeId ?? 0]);

        // Perform soft delete
        $stmt = $db->prepare("UPDATE `$tableName` SET deleted_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$recordId]);

        return [
            'status' => $success,
            'message' => $success
                ? "Record soft-deleted and logged for 30 days."
                : 'Soft delete failed.'
        ];

    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Soft delete error: ' . $e->getMessage()];
    }
}

function restoreFromDeletionLog($logId) {
    global $db;

    try {
        $stmt = $db->prepare(
            "SELECT * FROM deletion_log WHERE id = ? AND is_restored = 0 LIMIT 1"
        );
        $stmt->execute([$logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            return ['status' => false, 'message' => 'Log entry not found or already restored'];
        }

        $data = json_decode($log['snapshot_data'], true);
        if (!$data || !isset($data['id'])) {
            return ['status' => false, 'message' => 'Invalid snapshot data'];
        }

        $tableName = $log['table_name'];
        $data['deleted_at'] = null;
        $columns      = implode(', ', array_map(function ($c) {
            return '`' . str_replace('`', '``', $c) . '`';
        }, array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $setClauses   = implode(', ', array_map(function ($c) {
            $safe = str_replace('`', '``', $c);
            return '`' . $safe . '` = VALUES(`' . $safe . '`)';
        }, array_keys($data)));

        $stmt = $db->prepare(
            "INSERT INTO `$tableName` ($columns) VALUES ($placeholders)
             ON DUPLICATE KEY UPDATE $setClauses"
        );
        $stmt->execute(array_values($data));

        $stmt = $db->prepare(
            "UPDATE deletion_log SET is_restored = 1, restored_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$logId]);

        return ['status' => true, 'message' => "Record restored from $tableName."];

    } catch (Throwable $e) {
        return ['status' => false, 'message' => 'Restore error: ' . $e->getMessage()];
    }
}