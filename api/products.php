<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['products' => catalog(), 'fee' => DELIVERY_FEE], JSON_UNESCAPED_UNICODE);
