<?php
global $db;
require_once '../../autoload.php';
$auth = new Auth($db);
$auth->logout();
header("Location: " . BASE_URL . "index.php");
exit;
