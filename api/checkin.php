<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../inc/database.php';
require_once '../inc/loyalty_system.php';

// For demo purposes, we'll use a dummy user ID
// In a real application, you'd get this from session/JWT token
$demoUserId = $_GET['user_id'] ?? $_POST['user_id'] ?? 1;

$loyaltySystem = new LoyaltySystem();
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch ($action) {
        case 'checkin':
            // Process daily check-in
            $result = $loyaltySystem->processDailyCheckin($demoUserId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Check-in successful!',
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
            
        case 'status':
            // Get user's current loyalty status
            $userData = $loyaltySystem->getUserLoyaltyData($demoUserId);
            $canCheckin = $loyaltySystem->canCheckinToday($demoUserId);
            $calendar = $loyaltySystem->getCheckinCalendar($demoUserId);
            
            // Calculate next reward preview
            $nextStreak = $userData['current_streak'] + 1;
            $nextDayConfig = getNextDayReward($nextStreak);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user' => $userData,
                    'can_checkin_today' => $canCheckin,
                    'calendar' => $calendar,
                    'next_reward' => $nextDayConfig,
                    'vip_benefits' => getVipBenefits($userData['vip_tier']),
                    'streak_milestones' => getStreakMilestones($userData['current_streak'])
                ]
            ]);
            break;
            
        case 'history':
            // Get check-in history
            $limit = $_GET['limit'] ?? 30;
            $history = $loyaltySystem->getLoyaltyHistory($demoUserId, $limit);
            $calendar = $loyaltySystem->getCheckinCalendar($demoUserId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'transactions' => $history,
                    'checkin_calendar' => $calendar
                ]
            ]);
            break;
            
        case 'rewards_config':
            // Get daily rewards configuration for display
            $config = getDailyRewardsConfig();
            
            echo json_encode([
                'success' => true,
                'data' => $config
            ]);
            break;
            
        case 'leaderboard':
            // Get streak leaderboard
            $leaderboard = getStreakLeaderboard();
            
            echo json_encode([
                'success' => true,
                'data' => $leaderboard
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
 * Get next day reward configuration
 */
function getNextDayReward($nextDay) {
    global $pdo;
    
    $cycleDay = (($nextDay - 1) % 30) + 1;
    
    $stmt = $pdo->prepare("SELECT * FROM daily_rewards_config WHERE day_number = ? AND is_active = 1");
    $stmt->execute([$cycleDay]);
    $config = $stmt->fetch();
    
    if (!$config) {
        return [
            'day' => $nextDay,
            'points' => 50,
            'bonus_reward' => 'none',
            'description' => 'Daily check-in reward'
        ];
    }
    
    return [
        'day' => $nextDay,
        'cycle_day' => $cycleDay,
        'points' => $config['base_points'],
        'bonus_reward' => $config['bonus_reward_type'],
        'bonus_value' => $config['bonus_reward_value'],
        'description' => $config['description']
    ];
}

/**
 * Get VIP tier benefits
 */
function getVipBenefits($tier) {
    $benefits = [
        'bronze' => [
            'points_multiplier' => 1.0,
            'exclusive_raffles' => false,
            'priority_support' => false,
            'monthly_bonus' => 0,
            'tier_color' => '#CD7F32'
        ],
        'silver' => [
            'points_multiplier' => 1.1,
            'exclusive_raffles' => true,
            'priority_support' => false,
            'monthly_bonus' => 500,
            'tier_color' => '#C0C0C0'
        ],
        'gold' => [
            'points_multiplier' => 1.25,
            'exclusive_raffles' => true,
            'priority_support' => true,
            'monthly_bonus' => 1500,
            'tier_color' => '#FFD700'
        ],
        'diamond' => [
            'points_multiplier' => 1.5,
            'exclusive_raffles' => true,
            'priority_support' => true,
            'monthly_bonus' => 5000,
            'tier_color' => '#B9F2FF'
        ]
    ];
    
    return $benefits[$tier] ?? $benefits['bronze'];
}

/**
 * Get streak milestones information
 */
function getStreakMilestones($currentStreak) {
    $milestones = [
        7 => ['reward' => 'Free Ticket', 'bonus' => '10% points boost'],
        14 => ['reward' => '2 Free Tickets', 'bonus' => '25% points boost'],
        21 => ['reward' => '3 Free Tickets', 'bonus' => 'VIP benefits'],
        30 => ['reward' => '5 Free Tickets + 1000 points', 'bonus' => '50% points boost']
    ];
    
    $nextMilestone = null;
    foreach ($milestones as $day => $reward) {
        if ($currentStreak < $day) {
            $nextMilestone = [
                'day' => $day,
                'reward' => $reward,
                'days_remaining' => $day - $currentStreak
            ];
            break;
        }
    }
    
    return [
        'current_streak' => $currentStreak,
        'next_milestone' => $nextMilestone,
        'all_milestones' => $milestones
    ];
}

/**
 * Get daily rewards configuration for frontend display
 */
function getDailyRewardsConfig() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM daily_rewards_config ORDER BY day_number");
    return $stmt->fetchAll();
}

/**
 * Get streak leaderboard
 */
function getStreakLeaderboard($limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT name, current_streak, longest_streak, vip_tier, total_points_earned
        FROM users 
        WHERE current_streak > 0 
        ORDER BY current_streak DESC, longest_streak DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}
?> 