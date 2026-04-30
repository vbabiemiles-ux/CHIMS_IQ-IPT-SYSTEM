<?php
require_once '../../autoload.php';
$user = new User($db);

$encryptedId = $_GET['id'];
$user->id = $encryptedId;
$result = $user->readOne();

header('Content-Type: application/json');
echo json_encode($result);