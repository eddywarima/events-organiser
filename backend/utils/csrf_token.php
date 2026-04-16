<?php
require_once 'csrf.php';

header('Content-Type: application/json');

echo json_encode(['csrf_token' => CSRFProtection::getToken()]);
?>
