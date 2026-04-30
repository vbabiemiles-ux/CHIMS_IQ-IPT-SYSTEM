<?php
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

$user = new User($db);
$user->id = $_POST['id'];
$result = $user->delete();

echo json_encode($result);
exit;