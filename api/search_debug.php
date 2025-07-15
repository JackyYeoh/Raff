<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test database connection
    require_once '../inc/database.php';
    echo json_encode(['step' => 'Database connection successful']);
    exit;
    
} catch (Exception $e) {
    echo json_encode([
        'step' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit;
}
?> 