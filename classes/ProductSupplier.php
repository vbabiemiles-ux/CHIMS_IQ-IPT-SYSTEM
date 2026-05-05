<?php

class ProductSupplier {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function link($productId, $supplierId) {
        try {
            // Check already linked
            $stmt = $this->conn->prepare(
                "SELECT 1 FROM product_supplier
                 WHERE product_id = ? AND supplier_id = ?"
            );
            $stmt->execute([$productId, $supplierId]);
            if ($stmt->fetch()) {
                return ['status' => false, 'message' => 'Supplier already linked to this product.'];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO product_supplier (product_id, supplier_id) VALUES (?, ?)"
            );
            $success = $stmt->execute([$productId, $supplierId]);

            return [
                'status'  => $success,
                'message' => $success
                    ? 'Supplier linked successfully.'
                    : 'Failed to link supplier.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function getByProduct($productId) {
        try {
            $sql = "SELECT s.*
                    FROM suppliers s
                    JOIN product_supplier ps ON s.id = ps.supplier_id
                    WHERE ps.product_id = ?
                      AND " . tenantScope('s') . "
                      AND s.deleted_at IS NULL
                    ORDER BY s.supplier_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function unlink($productId, $supplierId) {
        try {
            // Verify the product belongs to this store before unlinking
            $check = $this->conn->prepare(
                "SELECT 1 FROM products
                 WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL"
            );
            $check->execute([$productId]);
            if (!$check->fetch()) {
                return ['status' => false, 'message' => 'Access denied.'];
            }

            $stmt = $this->conn->prepare(
                "DELETE FROM product_supplier
                 WHERE product_id = ? AND supplier_id = ?"
            );
            $success = $stmt->execute([$productId, $supplierId]);

            return [
                'status'  => $success,
                'message' => $success ? 'Supplier unlinked.' : 'Failed to unlink.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}