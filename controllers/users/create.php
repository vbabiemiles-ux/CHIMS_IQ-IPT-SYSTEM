<?php
global $db;
require_once '../../autoload.php';

//check CSRF
if (!csrf_check()) {
    $_SESSION['error'] = 'Invalid CSRF';
    header("Location: " . BASE_URL . "views/users/create.php");
    exit;
}

/* 
!!!ADD VALIDATION HERE DO NOT FORGET!!!!
*/
$user = new User($db);
$user->name = $_POST['name'];
$user->email = $_POST['email'];
$result = $user->create();

if ($result === true) {
    $_SESSION['success'] = "User created successfully!";
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result;
}

header("Location: " . BASE_URL . "views/users/index.php");
exit;