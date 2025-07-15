<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../inc/database.php';
require_once '../inc/loyalty_system.php';

$demoUserId = $_GET['user_id'] ?? $_POST['user_id'] ?? 1;
$loyaltySystem = new LoyaltySystem();
$action = $_GET['action'] ?? $_POST['action'] ?? 'items';

try {
    switch ($action) {
        case 'items':
            // Get all loyalty store items
            $items = getLoyaltyStoreItems($demoUserId);
            $userData = $loyaltySystem->getUserLoyaltyData($demoUserId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'user_points' => $userData['loyalty_points'],
                    'user_tier' => $userData['vip_tier']
                ]
            ]);
            break;
            
        case 'purchase':
            // Purchase an item from loyalty store
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                throw new Exception('Item ID required');
            }
            
            $result = purchaseLoyaltyItem($demoUserId, $itemId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Purchase successful!',
                    'data' => $result
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;
            
        case 'history':
            // Get user's purchase history
            $history = getLoyaltyPurchaseHistory($demoUserId);
            
            echo json_encode([
                'success' => true,
                'data' => $history
            ]);
            break;
            
        case 'redeem':
            // Redeem a purchased item (claim reward)
            $purchaseId = $_POST['purchase_id'] ?? null;
            if (!$purchaseId) {
                throw new Exception('Purchase ID required');
            }
            
            $result = redeemLoyaltyPurchase($demoUserId, $purchaseId);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Redemption processed',
                'data' => $result['data'] ?? null
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Get loyalty store items filtered by user's VIP tier
 */
function getLoyaltyStoreItems($userId) {
    global $pdo, $loyaltySystem;
    
    $userData = $loyaltySystem->getUserLoyaltyData($userId);
    $userTier = $userData['vip_tier'];
    $userPoints = $userData['loyalty_points'];
    
    // Define tier hierarchy for filtering
    $tierLevels = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'diamond' => 4];
    $userTierLevel = $tierLevels[$userTier];
    
    $stmt = $pdo->query("
        SELECT ls.*, 
               CASE 
                   WHEN ls.points_cost <= {$userPoints} THEN true 
                   ELSE false 
               END as can_afford,
               CASE 
                   WHEN ls.stock_quantity = -1 THEN 'unlimited'
                   WHEN ls.stock_quantity > 0 THEN 'available'
                   ELSE 'out_of_stock'
               END as stock_status
        FROM loyalty_store ls 
        WHERE ls.is_active = 1 
        ORDER BY ls.sort_order ASC, ls.points_cost ASC
    ");
    
    $items = [];
    foreach ($stmt->fetchAll() as $item) {
        $minTierLevel = $tierLevels[$item['min_vip_tier']];
        
        // Only show items user can access based on VIP tier
        if ($userTierLevel >= $minTierLevel) {
            $items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'image_url' => $item['image_url'],
                'points_cost' => intval($item['points_cost']),
                'item_type' => $item['item_type'],
                'item_value' => $item['item_value'],
                'min_vip_tier' => $item['min_vip_tier'],
                'can_afford' => $item['can_afford'],
                'stock_status' => $item['stock_status'],
                'stock_quantity' => $item['stock_quantity']
            ];
        }
    }
    
    return $items;
}

/**
 * Purchase an item from loyalty store
 */
function purchaseLoyaltyItem($userId, $itemId) {
    global $pdo, $loyaltySystem;
    
    try {
        $pdo->beginTransaction();
        
        // Get user data
        $userData = $loyaltySystem->getUserLoyaltyData($userId);
        if (!$userData) {
            throw new Exception('User not found');
        }
        
        // Get item details
        $stmt = $pdo->prepare("SELECT * FROM loyalty_store WHERE id = ? AND is_active = 1");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            throw new Exception('Item not found or not available');
        }
        
        // Check VIP tier requirement
        $tierLevels = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'diamond' => 4];
        $userTierLevel = $tierLevels[$userData['vip_tier']];
        $requiredTierLevel = $tierLevels[$item['min_vip_tier']];
        
        if ($userTierLevel < $requiredTierLevel) {
            throw new Exception('Insufficient VIP tier for this item');
        }
        
        // Check if user has enough points
        if ($userData['loyalty_points'] < $item['points_cost']) {
            throw new Exception('Insufficient loyalty points');
        }
        
        // Check stock availability
        if ($item['stock_quantity'] == 0) {
            throw new Exception('Item out of stock');
        }
        
        // Deduct points from user
        $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
        $stmt->execute([$item['points_cost'], $userId]);
        
        // Record purchase
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_purchases (user_id, store_item_id, points_spent, status)
            VALUES (?, ?, ?, 'completed')
        ");
        $stmt->execute([$userId, $itemId, $item['points_cost']]);
        $purchaseId = $pdo->lastInsertId();
        
        // Update stock if not unlimited
        if ($item['stock_quantity'] > 0) {
            $stmt = $pdo->prepare("UPDATE loyalty_store SET stock_quantity = stock_quantity - 1 WHERE id = ?");
            $stmt->execute([$itemId]);
        }
        
        // Record loyalty transaction
        $stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $newBalance = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_transactions 
            (user_id, transaction_type, points_change, balance_after, source_type, source_reference, description)
            VALUES (?, 'spent', ?, ?, 'purchase', ?, ?)
        ");
        $stmt->execute([
            $userId, -$item['points_cost'], $newBalance, 
            $purchaseId, "Purchased: {$item['name']}"
        ]);
        
        // Process the reward based on item type
        $rewardResult = processItemReward($userId, $item);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'purchase_id' => $purchaseId,
            'item' => $item,
            'reward_applied' => $rewardResult,
            'remaining_points' => $newBalance
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process item reward based on type
 */
function processItemReward($userId, $item) {
    global $pdo;
    
    switch ($item['item_type']) {
        case 'cash_reward':
            // Add to wallet balance
            $amount = floatval($item['item_value']);
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            return [
                'type' => 'cash_added',
                'amount' => $amount,
                'message' => "RM {$amount} added to your wallet"
            ];
            
        case 'vip_upgrade':
            // Upgrade VIP tier
            $newTier = $item['item_value'];
            $stmt = $pdo->prepare("UPDATE users SET vip_tier = ? WHERE id = ?");
            $stmt->execute([$newTier, $userId]);
            
            return [
                'type' => 'vip_upgraded',
                'new_tier' => $newTier,
                'message' => "VIP tier upgraded to {$newTier}!"
            ];
            
        case 'ticket_discount':
        case 'free_ticket':
        case 'exclusive_raffle':
            // These would need integration with your ticket/raffle system
            // For now, just return confirmation
            return [
                'type' => $item['item_type'],
                'value' => $item['item_value'],
                'message' => "Reward activated: {$item['name']}"
            ];
            
        default:
            return [
                'type' => 'unknown',
                'message' => "Reward processed: {$item['name']}"
            ];
    }
}

/**
 * Get user's loyalty purchase history
 */
function getLoyaltyPurchaseHistory($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT lp.*, ls.name as item_name, ls.description as item_description, 
               ls.item_type, ls.item_value, ls.image_url
        FROM loyalty_purchases lp
        JOIN loyalty_store ls ON lp.store_item_id = ls.id
        WHERE lp.user_id = ?
        ORDER BY lp.purchased_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll();
}

/**
 * Redeem a purchased item (for items that need manual redemption)
 */
function redeemLoyaltyPurchase($userId, $purchaseId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT lp.*, ls.name as item_name, ls.item_type, ls.item_value
            FROM loyalty_purchases lp
            JOIN loyalty_store ls ON lp.store_item_id = ls.id
            WHERE lp.id = ? AND lp.user_id = ? AND lp.status = 'completed'
        ");
        $stmt->execute([$purchaseId, $userId]);
        $purchase = $stmt->fetch();
        
        if (!$purchase) {
            throw new Exception('Purchase not found or already redeemed');
        }
        
        // Mark as redeemed (you might want to add a redeemed status)
        // For now, just return success
        
        return [
            'success' => true,
            'message' => "Successfully redeemed: {$purchase['item_name']}",
            'data' => $purchase
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?> 