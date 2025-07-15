<?php
require_once '../inc/database.php';
require_once '../inc/user_auth.php';

header('Content-Type: application/json');

// Get current user
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_popular_tags':
        // Get popular tags for autocomplete
        try {
            $stmt = $pdo->prepare("
                SELECT tag_name, usage_count 
                FROM popular_tags 
                ORDER BY usage_count DESC, last_used DESC 
                LIMIT 20
            ");
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $tags
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get popular tags: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_raffle_tags':
        // Get tags for a specific raffle
        $raffleId = $_GET['raffle_id'] ?? null;
        
        if (!$raffleId) {
            echo json_encode(['success' => false, 'error' => 'Raffle ID required']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT tag_name, tag_type 
                FROM raffle_tags 
                WHERE raffle_id = ? 
                ORDER BY tag_type, tag_name
            ");
            $stmt->execute([$raffleId]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $tags
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get raffle tags: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'add_tag':
        // Add tag to raffle (admin only)
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $raffleId = $input['raffle_id'] ?? null;
        $tagName = trim($input['tag_name'] ?? '');
        $tagType = $input['tag_type'] ?? 'custom';
        
        if (!$raffleId || !$tagName) {
            echo json_encode(['success' => false, 'error' => 'Raffle ID and tag name required']);
            break;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Add tag to raffle
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO raffle_tags (raffle_id, tag_name, tag_type) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$raffleId, $tagName, $tagType]);
            
            // Update popular tags
            $stmt = $pdo->prepare("
                INSERT INTO popular_tags (tag_name, usage_count) 
                VALUES (?, 1) 
                ON DUPLICATE KEY UPDATE 
                usage_count = usage_count + 1,
                last_used = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$tagName]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Tag added successfully'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => 'Failed to add tag: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'remove_tag':
        // Remove tag from raffle (admin only)
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $raffleId = $input['raffle_id'] ?? null;
        $tagName = $input['tag_name'] ?? '';
        
        if (!$raffleId || !$tagName) {
            echo json_encode(['success' => false, 'error' => 'Raffle ID and tag name required']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM raffle_tags 
                WHERE raffle_id = ? AND tag_name = ?
            ");
            $stmt->execute([$raffleId, $tagName]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tag removed successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to remove tag: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'search_by_tags':
        // Search raffles by tags
        $query = $_GET['q'] ?? '';
        $limit = min(20, intval($_GET['limit'] ?? 10));
        
        if (empty($query)) {
            echo json_encode(['success' => true, 'data' => []]);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT r.*, c.name as category_name, b.name as brand_name
                FROM raffles r
                LEFT JOIN categories c ON r.category_id = c.id
                LEFT JOIN brands b ON r.brand_id = b.id
                LEFT JOIN raffle_tags rt ON r.id = rt.raffle_id
                WHERE r.status = 'active' 
                AND (rt.tag_name LIKE ? OR r.title LIKE ? OR r.description LIKE ?)
                ORDER BY r.sold_tickets DESC
                LIMIT ?
            ");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            $raffles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $raffles
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to search raffles: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_user_preferences':
        // Get user's tag preferences for recommendations
        if (!$currentUser) {
            echo json_encode(['success' => true, 'data' => []]);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT tag_name, preference_score, interaction_count
                FROM user_tag_preferences
                WHERE user_id = ?
                ORDER BY preference_score DESC, interaction_count DESC
                LIMIT 10
            ");
            $stmt->execute([$currentUser['id']]);
            $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $preferences
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get user preferences: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'update_user_preference':
        // Update user's tag preference (called when user interacts with tagged raffle)
        if (!$currentUser) {
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $tagName = $input['tag_name'] ?? '';
        $interactionType = $input['interaction_type'] ?? 'view'; // view, wishlist, purchase
        
        if (!$tagName) {
            echo json_encode(['success' => false, 'error' => 'Tag name required']);
            break;
        }
        
        try {
            // Calculate preference score based on interaction type
            $scoreIncrement = 0;
            switch ($interactionType) {
                case 'view': $scoreIncrement = 0.1; break;
                case 'wishlist': $scoreIncrement = 0.3; break;
                case 'purchase': $scoreIncrement = 0.5; break;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO user_tag_preferences (user_id, tag_name, preference_score, interaction_count)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                preference_score = LEAST(2.0, preference_score + ?),
                interaction_count = interaction_count + 1,
                last_interaction = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                $currentUser['id'], 
                $tagName, 
                1.0 + $scoreIncrement, 
                $scoreIncrement
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Preference updated successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update preference: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action. Available actions: get_popular_tags, get_raffle_tags, add_tag, remove_tag, search_by_tags, get_user_preferences, update_user_preference'
        ]);
        break;
}
?> 