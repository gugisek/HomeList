<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json'); // Nagłówek JSON

include '../security_scripts.php';

echo json_encode([
    'status' => 'success',
    'message' => 'Test response'
]);
?>