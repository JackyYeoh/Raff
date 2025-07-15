<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../inc/database.php';

try {
    $category_id = $_GET['category_id'] ?? null;
    
    if (!$category_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        exit;
    }
    
    // Get brands for the specified category, ordered by featured status first
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.id, b.name, b.is_featured, bc.category_sort_order
        FROM brands b 
        INNER JOIN brand_categories bc ON b.id = bc.brand_id 
        WHERE bc.category_id = ? 
        ORDER BY b.is_featured DESC, bc.category_sort_order ASC, b.name ASC
    ");
    $stmt->execute([$category_id]);
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'brands' => $brands
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 