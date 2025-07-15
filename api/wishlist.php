<?php
require_once '../inc/database.php';
require_once '../inc/user_auth.php';

header('Content-Type: application/json');

// Get current user
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$userId = $currentUser['id'];
// Get action from URL parameters, POST data, or JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If action is not in URL or POST, try to get it from JSON body
if (empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

// Create wishlist table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_wishlists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            raffle_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_raffle (user_id, raffle_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (raffle_id) REFERENCES raffles(id) ON DELETE CASCADE
        )
    ");
} catch (Exception $e) {
    // Table might already exist
}

switch ($action) {
    case 'get':
        // Get user's wishlist
        try {
            $stmt = $pdo->prepare("
                SELECT raffle_id 
                FROM user_wishlists 
                WHERE user_id = ? 
                ORDER BY added_at DESC
            ");
            $stmt->execute([$userId]);
            $wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $wishlist
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load wishlist: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'update':
        // Update entire wishlist
        $input = json_decode(file_get_contents('php://input'), true);
        $wishlist = $input['wishlist'] ?? [];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Clear existing wishlist
            $stmt = $pdo->prepare("DELETE FROM user_wishlists WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Add new items
            if (!empty($wishlist)) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_wishlists (user_id, raffle_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($wishlist as $raffleId) {
                    $stmt->execute([$userId, $raffleId]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Wishlist updated successfully',
                'count' => count($wishlist)
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update wishlist: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'add':
        // Add single item to wishlist
        $input = json_decode(file_get_contents('php://input'), true);
        $raffleId = $input['raffle_id'] ?? null;
        
        if (!$raffleId) {
            echo json_encode(['success' => false, 'error' => 'Raffle ID required']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO user_wishlists (user_id, raffle_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $raffleId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Added to wishlist successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to add to wishlist: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'remove':
        // Remove single item from wishlist
        $input = json_decode(file_get_contents('php://input'), true);
        $raffleId = $input['raffle_id'] ?? null;
        
        if (!$raffleId) {
            echo json_encode(['success' => false, 'error' => 'Raffle ID required']);
            break;
        }
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM user_wishlists 
                WHERE user_id = ? AND raffle_id = ?
            ");
            $stmt->execute([$userId, $raffleId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Removed from wishlist successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to remove from wishlist: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'count':
        // Get wishlist count
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM user_wishlists 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get wishlist count: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action: "' . $action . '". Use: get, update, add, remove, or count'
        ]);
        break;
}
?> 