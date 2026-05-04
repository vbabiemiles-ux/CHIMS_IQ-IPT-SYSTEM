<?php

class StockFlag {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Staff submits a flag on a stock item
     */
    public function create($stockId, $note) {
        try {
            if (($_SESSION['role'] ?? '') !== 'staff') {
                return ['status' => false, 'message' => 'Only staff can submit flags.'];
            }

            // Verify the stock item belongs to this store
            $check = $this->conn->prepare(
                "SELECT s.id FROM stock s
                 JOIN products p ON s.product_id = p.id
                 WHERE s.id = ? AND " . tenantScope('p')
            );
            $check->execute([$stockId]);
            if (!$check->fetch()) {
                return ['status' => false, 'message' => 'Stock item not found or access denied.'];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO stock_flags (stock_id, flagged_by_user_id, note)
                 VALUES (?, ?, ?)"
            );
            $success = $stmt->execute([
                $stockId,
                $_SESSION['user_id'],
                trim($note)
            ]);

            return [
                'status'  => $success,
                'message' => $success
                    ? 'Flag submitted. Your admin has been notified.'
                    : 'Failed to submit flag.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Admin — get all unresolved flags for their store
     */
    public function getAll() {
        try {
            $sql = "SELECT sf.*,
                           u.full_name  AS flagged_by,
                           p.product_name,
                           p.id         AS product_id,
                           s.quantity,
                           s.health_status
                    FROM stock_flags sf
                    JOIN stock    s  ON sf.stock_id           = s.id
                    JOIN products p  ON s.product_id          = p.id
                    JOIN users    u  ON sf.flagged_by_user_id = u.id
                    WHERE " . tenantScope('p') . "
                      AND p.deleted_at IS NULL
                    ORDER BY sf.flagged_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Staff — get only their own flags
     */
    public function getMyFlags() {
        try {
            $sql = "SELECT sf.*,
                           p.product_name,
                           s.quantity,
                           s.health_status
                    FROM stock_flags sf
                    JOIN stock    s ON sf.stock_id    = s.id
                    JOIN products p ON s.product_id   = p.id
                    WHERE sf.flagged_by_user_id = ?
                      AND " . tenantScope('p') . "
                      AND p.deleted_at IS NULL
                    ORDER BY sf.flagged_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Admin resolves (deletes) a flag
     */
    public function resolve($flagId) {
        try {
            // Verify flag belongs to this store before deleting
            $check = $this->conn->prepare(
                "SELECT sf.id FROM stock_flags sf
                 JOIN stock    s ON sf.stock_id    = s.id
                 JOIN products p ON s.product_id   = p.id
                 WHERE sf.id = ? AND " . tenantScope('p')
            );
            $check->execute([$flagId]);
            if (!$check->fetch()) {
                return ['status' => false, 'message' => 'Flag not found or access denied.'];
            }

            $stmt = $this->conn->prepare(
                "DELETE FROM stock_flags WHERE id = ?"
            );
            $success = $stmt->execute([$flagId]);

            return [
                'status'  => $success,
                'message' => $success ? 'Flag resolved.' : 'Failed to resolve flag.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Count unresolved flags — for dashboard badge
     */
    public function countUnresolved() {
        try {
            $sql = "SELECT COUNT(*) FROM stock_flags sf
                    JOIN stock    s ON sf.stock_id  = s.id
                    JOIN products p ON s.product_id = p.id
                    WHERE " . tenantScope('p') . " AND p.deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}