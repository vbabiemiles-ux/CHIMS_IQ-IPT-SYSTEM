<?php
require_once '../../autoload.php';
$auth = new Auth($db);
$auth->logout();
header("Location: /index.php");
exit;
