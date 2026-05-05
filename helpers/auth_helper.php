<?php

/**
 * Require the user to be logged in
 * Redirect to login if not
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../views/auth/login.php");
        exit;
    }
}

/**
 * Require a specific role
 */
function requireRole($role) {
    requireLogin();
    $userRole = $_SESSION['role'] ?? '';
    if ($userRole === 'superadmin') {
        return;
    }
    if ($userRole !== $role) {
        header("Location: " . BASE_URL . "views/auth/login.php");
        exit;
    }
}

/**
 * Redirect if already logged in
 */
function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        $role = $_SESSION['role'];
        if ($role === 'superadmin') {
            header("Location: " . BASE_URL . "views/dashboard/superadmin.php");
        } elseif ($role === 'admin') {
            header("Location: " . BASE_URL . "views/dashboard/admin.php");
        } else {
            header("Location: " . BASE_URL . "views/dashboard/staff.php");
        }
        exit;
    }
}

/**
 * Get current user from session
 */
function currentUser() {
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'store_id'  => $_SESSION['store_id'] ?? null,
        'store_name'=> $_SESSION['store_name'] ?? '',
    ];
}

/**
 * Multi-tenant WHERE clause fragment
 * Usage: "WHERE " . tenantScope('p') . " AND ..."
 */
function tenantScope($alias = '') {
    $a = $alias ? $alias . '.' : '';
    if (($_SESSION['role'] ?? '') === 'superadmin') {
        return "1=1";
    }
    $storeId = (int)($_SESSION['store_id'] ?? 0);
    return $a . "store_id = " . $storeId;
}