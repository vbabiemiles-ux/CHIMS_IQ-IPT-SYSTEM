<?php
global $db;
require_once '../../autoload.php';

header('Content-Type: application/json');
// check CSRF
if (!csrf_check()) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid CSRF"
    ]);
    exit;
}

/* 
!!!ADD VALIDATION HERE DO NOT FORGET!!!!
*/

$user = new User($db);
$user->name = $_POST['name'];
$user->email = $_POST['email'];
$user->id = $_POST['id'];

$result = $user->update();

if ($result['status']) {
    $_SESSION['success'] = $result['message'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../../views/users/index.php");
exit;