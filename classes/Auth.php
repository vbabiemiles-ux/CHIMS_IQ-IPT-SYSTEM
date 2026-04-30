<?php

class Auth {

    private $conn;
    private $usersTable = "users";
    private $storesTable = "stores";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT u.*, s.name as store_name
                 FROM " . $this->usersTable . " u
                 LEFT JOIN " . $this->storesTable . " s ON u.store_id = s.id
                 WHERE u.email = :email AND u.deleted_at IS NULL
                 LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return ['status' => false, 'message' => 'Invalid email or password.'];
            }

            // Store session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['store_id']   = $user['store_id'];
            $_SESSION['store_name'] = $user['store_name'] ?? '';

            return ['status' => true, 'role' => $user['role']];

        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Register admin + store
     */
    public function registerAdmin($fullName, $email, $storeName, $password) {
        try {
            // Check email uniqueness
            $stmt = $this->conn->prepare("SELECT id FROM " . $this->usersTable . " WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['status' => false, 'message' => 'Email already registered.'];
            }

            $this->conn->beginTransaction();

            // Create store
            $stmt = $this->conn->prepare("INSERT INTO " . $this->storesTable . " (name) VALUES (?)");
            $stmt->execute([$storeName]);
            $storeId = $this->conn->lastInsertId();

            // Create admin user
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->conn->prepare(
                "INSERT INTO " . $this->usersTable . " (store_id, full_name, email, password, role)
                 VALUES (?, ?, ?, ?, 'admin')"
            );
            $stmt->execute([$storeId, $fullName, $email, $hashed]);

            $this->conn->commit();

            return ['status' => true];

        } catch (Throwable $e) {
            $this->conn->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Logout
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
}
