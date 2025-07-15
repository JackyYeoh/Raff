<?php
header('Content-Type: application/json');

// Fix the path to database.php
$database_path = __DIR__ . '/../inc/database.php';
if (!file_exists($database_path)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database configuration file not found: ' . $database_path
    ]);
    exit;
}

require_once $database_path;

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'track_search':
            trackSearch();
            break;
        case 'get_popular_searches':
            getPopularSearches();
            break;
        case 'get_search_suggestions':
            getSearchSuggestions();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function trackSearch() {
    global $pdo;
    
    $query = $_POST['query'] ?? $_GET['query'] ?? '';
    $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
    $results_count = $_POST['results_count'] ?? $_GET['results_count'] ?? 0;
    $filters = $_POST['filters'] ?? $_GET['filters'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Query required']);
        return;
    }
    
    // Create search_logs table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS search_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            query VARCHAR(255) NOT NULL,
            user_id INT NULL,
            results_count INT DEFAULT 0,
            filters TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_query (query),
            INDEX idx_created_at (created_at),
            INDEX idx_user_id (user_id)
        )
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO search_logs (query, user_id, results_count, filters, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $query,
        $user_id,
        $results_count,
        $filters,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    echo json_encode(['success' => true]);
}

function getPopularSearches() {
    global $pdo;
    
    // Check if search_logs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'search_logs'");
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    // Get popular searches from last 30 days
    $stmt = $pdo->query("
        SELECT query, COUNT(*) as search_count
        FROM search_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY query
        ORDER BY search_count DESC
        LIMIT 10
    ");
    
    $popular_searches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $popular_searches]);
}

function getSearchSuggestions() {
    global $pdo;
    
    $query = $_GET['q'] ?? '';
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $suggestions = [];
    
    // Get popular searches that match the query
    $stmt = $pdo->query("SHOW TABLES LIKE 'search_logs'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT query, COUNT(*) as search_count
            FROM search_logs
            WHERE query LIKE ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY query
            ORDER BY search_count DESC
            LIMIT 5
        ");
        $stmt->execute(["%$query%"]);
        $suggestions['popular_searches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get trending categories
    $stmt = $pdo->prepare("
        SELECT c.name, COUNT(*) as raffle_count
        FROM raffles r
        LEFT JOIN categories c ON r.category_id = c.id
        WHERE r.status = 'active' AND c.name LIKE ?
        GROUP BY c.name
        ORDER BY raffle_count DESC
        LIMIT 5
    ");
    $stmt->execute(["%$query%"]);
    $suggestions['trending_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get popular tags that match the query
    $stmt = $pdo->query("SHOW TABLES LIKE 'popular_tags'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT tag_name, usage_count
            FROM popular_tags
            WHERE tag_name LIKE ?
            ORDER BY usage_count DESC, last_used DESC
            LIMIT 5
        ");
        $stmt->execute(["%$query%"]);
        $suggestions['popular_tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get trending brands
    $stmt = $pdo->prepare("
        SELECT b.name, COUNT(*) as raffle_count
        FROM raffles r
        LEFT JOIN brands b ON r.brand_id = b.id
        WHERE r.status = 'active' AND b.name LIKE ?
        GROUP BY b.name
        ORDER BY raffle_count DESC
        LIMIT 5
    ");
    $stmt->execute(["%$query%"]);
    $suggestions['trending_brands'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $suggestions]);
}
?> 