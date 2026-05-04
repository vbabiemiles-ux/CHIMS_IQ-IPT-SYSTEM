<?php

class Supplier {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($supplierName, $phone, $email, $address) {
        try {
            $storeId = $_SESSION['store_id'] ?? null;
            if (!$storeId && ($_SESSION['role'] ?? '') !== 'superadmin') {
                return ['status' => false, 'message' => 'Store context required'];
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO suppliers (store_id, supplier_name, phone, email, address)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $success = $stmt->execute([
                $storeId,
                trim($supplierName),
                trim($phone),
                trim($email),
                trim($address)
            ]);

            return [
                'status'  => $success,
                'message' => $success
                    ? 'Supplier created successfully.'
                    : 'Failed to create supplier.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAll() {
        try {
            $sql = "SELECT * FROM suppliers
                    WHERE " . tenantScope() . " AND deleted_at IS NULL
                    ORDER BY supplier_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT * FROM suppliers
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function update($id, $supplierName, $phone, $email, $address) {
        try {
            $sql = "UPDATE suppliers
                    SET supplier_name = ?, phone = ?, email = ?, address = ?
                    WHERE id = ? AND " . tenantScope() . " AND deleted_at IS NULL";
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                trim($supplierName),
                trim($phone),
                trim($email),
                trim($address),
                $id
            ]);

            return [
                'status'  => $success && $stmt->rowCount() > 0,
                'message' => $success
                    ? 'Supplier updated successfully.'
                    : 'Failed to update or access denied.'
            ];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function softDelete($id) {
        return softDeleteRecord('suppliers', $id, $_SESSION['user_id'] ?? 0);
    }
}