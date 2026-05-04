<?php

class Category {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($categoryName, $description = null) {
        try {
            $storeId = $_SESSION['store_id'] ?? null;
            if (!$storeId && ($_SESSION['role'] ?? '') !== 'superadmin') {
                return ['status' => false, 'message' => 'Store context required'];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO categories (store_id, category_name, description)
                 VALUES (?, ?, ?)"
            );
            $success = $stmt->execute([
                $storeId,
                trim($categoryName),
                trim($description ?? '')
            ]);

            return [
                'status'  => $success,
                'message' => $success
                    ? 'Category created successfully.'
                    : 'Failed to create category.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll() {
        try {
            $sql = "SELECT * FROM categories 
                    WHERE " . tenantScope() . " AND deleted_at IS NULL 
                    ORDER BY category_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT * FROM categories 
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function update($id, $categoryName, $description = null) {
        try {
            $sql = "UPDATE categories 
                    SET category_name = ?, description = ?
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                trim($categoryName),
                trim($description ?? ''),
                $id
            ]);

            return [
                'status'  => $success && $stmt->rowCount() > 0,
                'message' => $success
                    ? 'Category updated successfully.'
                    : 'Failed to update or access denied.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function softDelete($id) {
        return softDeleteRecord('categories', $id, $_SESSION['user_id'] ?? 0);
    }
}