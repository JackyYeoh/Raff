<?php
require_once 'database.php';

class LoyaltySystem {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Process daily check-in for a user
     */
    public function processDailyCheckin($userId) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if user already checked in today
            $today = date('Y-m-d');
            $stmt = $this->pdo->prepare("SELECT * FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
            $stmt->execute([$userId, $today]);
            
            if ($stmt->fetch()) {
                throw new Exception('Already checked in today');
            }
            
            // Get user current data
            $user = $this->getUserLoyaltyData($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Calculate streak
            $newStreak = $this->calculateNewStreak($userId, $user['last_checkin_date']);
            
            // Get reward for this day in streak
            $rewardConfig = $this->getDailyRewardConfig($newStreak);
            
            // Calculate final points with bonuses
            $basePoints = $rewardConfig['base_points'];
            $bonusMultiplier = $this->calculateBonusMultiplier($userId, $newStreak);
            $finalPoints = round($basePoints * $bonusMultiplier);
            
            // Process weekend bonus
            $isWeekend = in_array(date('w'), [0, 6]); // Sunday = 0, Saturday = 6
            if ($isWeekend && $rewardConfig['weekend_multiplier'] > 1) {
                $finalPoints = round($finalPoints * $rewardConfig['weekend_multiplier']);
            }
            
            // Record the check-in
            $stmt = $this->pdo->prepare("
                INSERT INTO daily_checkins 
                (user_id, checkin_date, day_in_streak, points_awarded, bonus_reward, is_weekend_bonus)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $bonusReward = $this->formatBonusReward($rewardConfig);
            $stmt->execute([
                $userId, $today, $newStreak, $finalPoints, 
                $bonusReward, $isWeekend
            ]);
            
            // Update user loyalty data
            $this->updateUserLoyaltyData($userId, $finalPoints, $newStreak);
            
            // Record loyalty transaction
            $this->recordLoyaltyTransaction($userId, 'earned', $finalPoints, 'checkin', 
                "Daily check-in Day {$newStreak}");
            
            // Process bonus rewards if any
            $bonusRewards = $this->processBonusRewards($userId, $rewardConfig);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'points_earned' => $finalPoints,
                'new_streak' => $newStreak,
                'bonus_rewards' => $bonusRewards,
                'user_data' => $this->getUserLoyaltyData($userId),
                'next_reward' => $this->getNextDayReward($newStreak + 1)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Calculate new streak based on last check-in
     */
    private function calculateNewStreak($userId, $lastCheckinDate) {
        if (!$lastCheckinDate) {
            return 1; // First time checking in
        }
        
        $lastDate = new DateTime($lastCheckinDate);
        $today = new DateTime();
        $daysDiff = $today->diff($lastDate)->days;
        
        if ($daysDiff == 1) {
            // Consecutive day - continue streak
            $stmt = $this->pdo->prepare("SELECT current_streak FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentStreak = $stmt->fetchColumn();
            return $currentStreak + 1;
        } elseif ($daysDiff == 0) {
            // Same day - shouldn't happen due to check above
            throw new Exception('Already checked in today');
        } else {
            // Broke streak - start over
            return 1;
        }
    }
    
    /**
     * Get daily reward configuration for specific day
     */
    private function getDailyRewardConfig($dayNumber) {
        // Cap at 30 days, then cycle
        $cycleDay = (($dayNumber - 1) % 30) + 1;
        
        $stmt = $this->pdo->prepare("SELECT * FROM daily_rewards_config WHERE day_number = ? AND is_active = 1");
        $stmt->execute([$cycleDay]);
        $config = $stmt->fetch();
        
        if (!$config) {
            // Fallback default
            return [
                'base_points' => 50,
                'bonus_reward_type' => 'none',
                'bonus_reward_value' => null,
                'weekend_multiplier' => 1.5,
                'description' => 'Daily check-in reward'
            ];
        }
        
        return $config;
    }
    
    /**
     * Calculate bonus multiplier based on VIP tier and special events
     */
    private function calculateBonusMultiplier($userId, $streak) {
        $user = $this->getUserLoyaltyData($userId);
        $multiplier = 1.0;
        
        // VIP tier bonuses
        switch ($user['vip_tier']) {
            case 'silver': $multiplier += 0.1; break;
            case 'gold': $multiplier += 0.25; break;
            case 'diamond': $multiplier += 0.5; break;
        }
        
        // Long streak bonuses
        if ($streak >= 30) $multiplier += 0.5;
        elseif ($streak >= 14) $multiplier += 0.25;
        elseif ($streak >= 7) $multiplier += 0.1;
        
        return $multiplier;
    }
    
    /**
     * Update user loyalty data after check-in
     */
    private function updateUserLoyaltyData($userId, $pointsEarned, $newStreak) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET 
                loyalty_points = loyalty_points + ?,
                total_points_earned = total_points_earned + ?,
                current_streak = ?,
                longest_streak = GREATEST(longest_streak, ?),
                last_checkin_date = CURDATE(),
                vip_points = vip_points + ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        // VIP points are earned at 10% rate of loyalty points
        $vipPointsEarned = round($pointsEarned * 0.1);
        
        $stmt->execute([
            $pointsEarned, $pointsEarned, $newStreak, $newStreak, 
            $vipPointsEarned, $userId
        ]);
        
        // Check for VIP tier upgrade
        $this->checkVipTierUpgrade($userId);
    }
    
    /**
     * Check and upgrade VIP tier if qualified
     */
    private function checkVipTierUpgrade($userId) {
        $user = $this->getUserLoyaltyData($userId);
        $vipPoints = $user['vip_points'];
        $currentTier = $user['vip_tier'];
        
        $newTier = $currentTier;
        if ($vipPoints >= 10000 && $currentTier != 'diamond') $newTier = 'diamond';
        elseif ($vipPoints >= 5000 && $currentTier == 'bronze' || $currentTier == 'silver') $newTier = 'gold';
        elseif ($vipPoints >= 1000 && $currentTier == 'bronze') $newTier = 'silver';
        
        if ($newTier != $currentTier) {
            $stmt = $this->pdo->prepare("UPDATE users SET vip_tier = ? WHERE id = ?");
            $stmt->execute([$newTier, $userId]);
            
            // Give upgrade bonus
            $bonusPoints = $this->getVipUpgradeBonus($newTier);
            $stmt = $this->pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$bonusPoints, $userId]);
            
            $this->recordLoyaltyTransaction($userId, 'bonus', $bonusPoints, 'vip_upgrade', 
                "VIP tier upgraded to {$newTier}");
        }
    }
    
    /**
     * Get VIP upgrade bonus points
     */
    private function getVipUpgradeBonus($tier) {
        switch ($tier) {
            case 'silver': return 500;
            case 'gold': return 1500;
            case 'diamond': return 5000;
            default: return 0;
        }
    }
    
    /**
     * Process bonus rewards (free tickets, spins, etc.)
     */
    private function processBonusRewards($userId, $rewardConfig) {
        $bonusRewards = [];
        
        if ($rewardConfig['bonus_reward_type'] != 'none') {
            switch ($rewardConfig['bonus_reward_type']) {
                case 'ticket':
                    $tickets = intval($rewardConfig['bonus_reward_value']);
                    $bonusRewards[] = [
                        'type' => 'free_tickets',
                        'value' => $tickets,
                        'description' => "{$tickets} free raffle ticket(s)"
                    ];
                    // Could implement actual free ticket logic here
                    break;
                    
                case 'spin':
                    $spins = intval($rewardConfig['bonus_reward_value']);
                    $bonusRewards[] = [
                        'type' => 'spin_tokens',
                        'value' => $spins,
                        'description' => "{$spins} lucky spin(s)"
                    ];
                    $this->addSpinTokens($userId, $spins);
                    break;
                    
                case 'discount':
                    $discount = $rewardConfig['bonus_reward_value'];
                    $bonusRewards[] = [
                        'type' => 'discount_coupon',
                        'value' => $discount,
                        'description' => "{$discount} discount on next purchase"
                    ];
                    $this->addDiscountCoupon($userId, $discount);
                    break;
                    
                case 'multiplier':
                    $bonusRewards[] = [
                        'type' => 'multiplier_applied',
                        'value' => $rewardConfig['bonus_reward_value'],
                        'description' => "Points multiplier applied"
                    ];
                    break;
            }
        }
        
        return $bonusRewards;
    }
    
    /**
     * Record loyalty point transaction
     */
    private function recordLoyaltyTransaction($userId, $type, $pointsChange, $source, $description) {
        // Get current balance
        $stmt = $this->pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $balance = $stmt->fetchColumn();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO loyalty_transactions 
            (user_id, transaction_type, points_change, balance_after, source_type, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $pointsChange, $balance, $source, $description]);
    }
    
    /**
     * Get user loyalty data
     */
    public function getUserLoyaltyData($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, name, email, loyalty_points, total_points_earned, current_streak, 
                   longest_streak, last_checkin_date, vip_tier, vip_points, wallet_balance,
                   created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Check if user can check in today
     */
    public function canCheckinToday($userId) {
        $today = date('Y-m-d');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
        $stmt->execute([$userId, $today]);
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * Get user's check-in calendar for current month
     */
    public function getCheckinCalendar($userId, $month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        
        $stmt = $this->pdo->prepare("
            SELECT checkin_date, day_in_streak, points_awarded, bonus_reward
            FROM daily_checkins 
            WHERE user_id = ? AND MONTH(checkin_date) = ? AND YEAR(checkin_date) = ?
            ORDER BY checkin_date
        ");
        $stmt->execute([$userId, $month, $year]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get next day reward preview
     */
    private function getNextDayReward($nextDay) {
        $cycleDay = (($nextDay - 1) % 30) + 1;
        $config = $this->getDailyRewardConfig($cycleDay);
        
        return [
            'day' => $nextDay,
            'points' => $config['base_points'],
            'bonus_reward' => $config['bonus_reward_type'],
            'description' => $config['description']
        ];
    }
    
    /**
     * Format bonus reward for display
     */
    private function formatBonusReward($config) {
        if ($config['bonus_reward_type'] == 'none') {
            return null;
        }
        
        return json_encode([
            'type' => $config['bonus_reward_type'],
            'value' => $config['bonus_reward_value']
        ]);
    }
    
    /**
     * Add spin tokens to user (placeholder for now)
     */
    private function addSpinTokens($userId, $count) {
        // This would integrate with your spin wheel system
        // For now, just record as loyalty transaction
        $this->recordLoyaltyTransaction($userId, 'bonus', 0, 'spin', 
            "Earned {$count} spin token(s)");
    }
    
    /**
     * Add discount coupon (placeholder for now)
     */
    private function addDiscountCoupon($userId, $discount) {
        // This would integrate with your coupon system
        $this->recordLoyaltyTransaction($userId, 'bonus', 0, 'discount', 
            "Earned {$discount} discount coupon");
    }
    
    /**
     * Get user's loyalty transaction history
     */
    public function getLoyaltyHistory($userId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM loyalty_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get platform statistics for loyalty system
     */
    public function getLoyaltyStats() {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(loyalty_points) as total_points_in_circulation,
                SUM(total_points_earned) as total_points_distributed,
                SUM(current_streak) as total_active_streaks,
                AVG(current_streak) as avg_streak,
                MAX(longest_streak) as max_streak_ever,
                COUNT(CASE WHEN vip_tier = 'silver' THEN 1 END) as silver_users,
                COUNT(CASE WHEN vip_tier = 'gold' THEN 1 END) as gold_users,
                COUNT(CASE WHEN vip_tier = 'diamond' THEN 1 END) as diamond_users
            FROM users
        ");
        return $stmt->fetch();
    }
}
?> 