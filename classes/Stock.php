<?php

class Stock {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Recalculate and update health status for one product
     */
    private function calculateHealth($quantity, $minLevel) {
        if ($quantity <= 0)          return 'Critical';
        if ($quantity <= $minLevel)  return 'Low';
        return 'Healthy';
    }

    /**
     * Update quantity + min level, auto-recalculate health
     */
    public function updateQuantity($productId, $newQuantity, $minStockLevel = null) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id, min_stock_level FROM stock WHERE product_id = ? LIMIT 1"
            );
            $stmt->execute([$productId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $minLevel = ($minStockLevel !== null)
                ? (int)$minStockLevel
                : ($existing['min_stock_level'] ?? 5);

            $qty    = max(0, (int)$newQuantity);
            $health = $this->calculateHealth($qty, $minLevel);

            if ($existing) {
                $stmt = $this->conn->prepare(
                    "UPDATE stock
                     SET quantity = ?, min_stock_level = ?, health_status = ?
                     WHERE product_id = ?"
                );
                $stmt->execute([$qty, $minLevel, $health, $productId]);
            } else {
                // Safety fallback: create missing stock row
                $stmt = $this->conn->prepare(
                    "INSERT INTO stock (product_id, quantity, min_stock_level, health_status)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$productId, $qty, $minLevel, $health]);
            }

            return [
                'status'  => true,
                'health'  => $health,
                'message' => "Stock updated. Health: $health"
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all stock with product + category info,
     * sorted Critical → Low → Healthy
     */
    public function getAllWithHealth() {
        try {
            $sql = "SELECT s.*,
                           p.product_name, p.price, p.brand,
                           c.category_name
                    FROM stock s
                    JOIN products p  ON s.product_id = p.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE " . tenantScope('p') . "
                      AND p.deleted_at IS NULL
                    ORDER BY
                        CASE s.health_status
                            WHEN 'Critical' THEN 1
                            WHEN 'Low'      THEN 2
                            ELSE 3
                        END,
                        p.product_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Summary counts for dashboard
     */
    public function getHealthSummary() {
        try {
            $sql = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN s.health_status = 'Healthy'  THEN 1 ELSE 0 END) as healthy,
                        SUM(CASE WHEN s.health_status = 'Low'      THEN 1 ELSE 0 END) as low,
                        SUM(CASE WHEN s.health_status = 'Critical' THEN 1 ELSE 0 END) as critical
                    FROM stock s
                    JOIN products p ON s.product_id = p.id
                    WHERE " . tenantScope('p') . " AND p.deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return ['total' => 0, 'healthy' => 0, 'low' => 0, 'critical' => 0];
        }
    }

    /**
     * Get only critical items (for dashboard alert + auto PO)
     */
    public function getCritical() {
        try {
            $sql = "SELECT s.*, p.product_name, p.id as pid
                    FROM stock s
                    JOIN products p ON s.product_id = p.id
                    WHERE s.health_status = 'Critical'
                      AND " . tenantScope('p') . "
                      AND p.deleted_at IS NULL
                    ORDER BY p.product_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}