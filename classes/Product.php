<?php

class Product {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($categoryId, $productName, $description = null, $price = 0.00, $brand = null) {
        try {
            $storeId = $_SESSION['store_id'] ?? null;
            if (!$storeId && ($_SESSION['role'] ?? '') !== 'superadmin') {
                return ['status' => false, 'message' => 'Store context required'];
            }

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "INSERT INTO products (store_id, category_id, product_name, description, price, brand)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $success = $stmt->execute([
                $storeId,
                (int)$categoryId,
                trim($productName),
                trim($description ?? ''),
                (float)$price,
                trim($brand ?? '')
            ]);

            if ($success) {
                $productId = $this->conn->lastInsertId();
                // Auto-create stock record at 0
                $stockStmt = $this->conn->prepare(
                    "INSERT INTO stock (product_id, quantity, min_stock_level, health_status)
                     VALUES (?, 0, 5, 'Critical')"
                );
                $stockStmt->execute([$productId]);
            }

            $this->conn->commit();

            return [
                'status'  => $success,
                'message' => $success
                    ? 'Product created successfully. Stock record initialized.'
                    : 'Failed to create product.'
            ];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll() {
        try {
            $sql = "SELECT p.*, c.category_name,
                           s.quantity, s.health_status
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN stock s ON s.product_id = p.id
                    WHERE " . tenantScope('p') . " AND p.deleted_at IS NULL
                    ORDER BY p.product_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT * FROM products
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function update($id, $categoryId, $productName, $description, $price, $brand) {
        try {
            $sql = "UPDATE products
                    SET category_id = ?, product_name = ?,
                        description = ?, price = ?, brand = ?
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                (int)$categoryId,
                trim($productName),
                trim($description ?? ''),
                (float)$price,
                trim($brand ?? ''),
                (int)$id
            ]);

            return [
                'status'  => $success && $stmt->rowCount() > 0,
                'message' => $success
                    ? 'Product updated successfully.'
                    : 'Failed to update or access denied.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function softDelete($id) {
        return softDeleteRecord('products', $id, $_SESSION['user_id'] ?? 0);
    }
}