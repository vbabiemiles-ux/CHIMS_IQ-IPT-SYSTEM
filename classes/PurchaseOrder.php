<?php

class PurchaseOrder {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a manual PO
     */
    public function create($supplierId, $orderDate, $expectedDate = null, $items = []) {
        try {
            $storeId = $_SESSION['store_id'] ?? null;
            if (!$storeId) {
                return ['status' => false, 'message' => 'Store context required.'];
            }
            if (empty($items)) {
                return ['status' => false, 'message' => 'At least one item is required.'];
            }

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "INSERT INTO purchase_orders
                 (store_id, supplier_id, created_by_user_id, order_date, expected_date, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $storeId,
                (int)$supplierId,
                $_SESSION['user_id'],
                $orderDate,
                $expectedDate ?: null
            ]);
            $poId = $this->conn->lastInsertId();

            $itemStmt = $this->conn->prepare(
                "INSERT INTO purchase_order_items
                 (purchase_order_id, product_id, quantity_ordered, unit_cost)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    $poId,
                    (int)$item['product_id'],
                    (int)$item['quantity_ordered'],
                    (float)$item['unit_cost']
                ]);
            }

            $this->conn->commit();
            return ['status' => true, 'po_id' => $poId,
                    'message' => 'Purchase order created successfully.'];

        } catch (Throwable $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Auto-generate PO drafts for all Critical stock items
     * Groups by supplier — one PO per supplier
     */
    public function generateAutoDrafts() {
        try {
            $storeId = $_SESSION['store_id'] ?? null;
            if (!$storeId) {
                return ['status' => false, 'message' => 'Store context required.'];
            }

            // Get all critical items that have at least one supplier linked
            $sql = "SELECT s.product_id, p.product_name,
                           s.min_stock_level, s.quantity,
                           ps.supplier_id
                    FROM stock s
                    JOIN products p        ON s.product_id  = p.id
                    JOIN product_supplier ps ON p.id        = ps.product_id
                    WHERE s.health_status = 'Critical'
                      AND " . tenantScope('p') . "
                      AND p.deleted_at IS NULL";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $criticalItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($criticalItems)) {
                return [
                    'status'  => true,
                    'message' => 'No critical items with linked suppliers found.'
                ];
            }

            // Group items by supplier_id
            $grouped = [];
            foreach ($criticalItems as $item) {
                $sid = $item['supplier_id'];
                if (!isset($grouped[$sid])) {
                    $grouped[$sid] = [];
                }
                $grouped[$sid][] = $item;
            }

            $this->conn->beginTransaction();

            $created = 0;
            foreach ($grouped as $supplierId => $items) {
                // Create one PO per supplier
                $stmt = $this->conn->prepare(
                    "INSERT INTO purchase_orders
                     (store_id, supplier_id, created_by_user_id,
                      order_date, status)
                     VALUES (?, ?, ?, CURDATE(), 'pending')"
                );
                $stmt->execute([$storeId, $supplierId, $_SESSION['user_id']]);
                $poId = $this->conn->lastInsertId();

                $itemStmt = $this->conn->prepare(
                    "INSERT INTO purchase_order_items
                     (purchase_order_id, product_id, quantity_ordered, unit_cost)
                     VALUES (?, ?, ?, 0.00)"
                );
                foreach ($items as $item) {
                    // Suggest restock to 2× min level
                    $suggestedQty = max(10, $item['min_stock_level'] * 2);
                    $itemStmt->execute([$poId, $item['product_id'], $suggestedQty]);
                }
                $created++;
            }

            $this->conn->commit();
            return [
                'status'  => true,
                'message' => "$created auto PO draft(s) created for critical stock."
            ];

        } catch (Throwable $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all POs for this store
     */
    public function getAll() {
        try {
            $sql = "SELECT po.*,
                           s.supplier_name,
                           u.full_name AS created_by,
                           COUNT(poi.id) AS item_count,
                           SUM(poi.quantity_ordered * poi.unit_cost) AS total_cost
                    FROM purchase_orders po
                    JOIN suppliers s   ON po.supplier_id        = s.id
                    JOIN users u       ON po.created_by_user_id = u.id
                    LEFT JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                    WHERE " . tenantScope('po') . "
                    GROUP BY po.id
                    ORDER BY po.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get one PO with its items
     */
    public function getById($poId) {
        try {
            $sql = "SELECT po.*,
                           s.supplier_name, s.phone AS supplier_phone,
                           s.email AS supplier_email,
                           u.full_name AS created_by
                    FROM purchase_orders po
                    JOIN suppliers s ON po.supplier_id        = s.id
                    JOIN users u     ON po.created_by_user_id = u.id
                    WHERE po.id = ? AND " . tenantScope('po');
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$poId]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($po) {
                $itemStmt = $this->conn->prepare(
                    "SELECT poi.*, p.product_name, p.brand
                     FROM purchase_order_items poi
                     JOIN products p ON poi.product_id = p.id
                     WHERE poi.purchase_order_id = ?"
                );
                $itemStmt->execute([$poId]);
                $po['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $po;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Update PO status
     * When marked 'received' — auto-update stock quantities
     */
    public function updateStatus($poId, $newStatus) {
        try {
            $po = $this->getById($poId);
            if (!$po) {
                return ['status' => false, 'message' => 'PO not found or access denied.'];
            }
            if ($po['status'] === 'received') {
                return ['status' => false, 'message' => 'This PO is already marked received.'];
            }

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "UPDATE purchase_orders SET status = ? WHERE id = ? AND " . tenantScope()
            );
            $stmt->execute([$newStatus, $poId]);

            // If marked received → add quantities to stock
            if ($newStatus === 'received' && !empty($po['items'])) {
                $stockObj = new Stock($this->conn);
                foreach ($po['items'] as $item) {
                    // Get current qty first
                    $qtyStmt = $this->conn->prepare(
                        "SELECT quantity, min_stock_level FROM stock WHERE product_id = ?"
                    );
                    $qtyStmt->execute([$item['product_id']]);
                    $current = $qtyStmt->fetch(PDO::FETCH_ASSOC);

                    $newQty = ($current['quantity'] ?? 0) + $item['quantity_ordered'];
                    $stockObj->updateQuantity(
                        $item['product_id'],
                        $newQty,
                        $current['min_stock_level'] ?? 5
                    );
                }
            }

            $this->conn->commit();
            return [
                'status'  => true,
                'message' => 'PO status updated' .
                             ($newStatus === 'received' ? ' and stock quantities adjusted.' : '.')
            ];

        } catch (Throwable $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function countByStatus($status) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM purchase_orders
                 WHERE status = ? AND " . tenantScope()
            );
            $stmt->execute([$status]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}