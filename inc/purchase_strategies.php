<?php
// Purchase Strategies for RM1 Ticket Raffles
// Optimized for user psychology and engagement

class PurchaseStrategies {
    
    /**
     * Get recommended purchase quantities based on user behavior and raffle data
     */
    public static function getRecommendedQuantities($raffle, $userTickets = 0, $userBudget = 0) {
        $remaining = $raffle['total_tickets'] - $raffle['sold_tickets'];
        $completionRate = $raffle['sold_tickets'] / $raffle['total_tickets'];
        
        $strategies = [
            // Quick Entry Options
            'quick_entry' => [
                'title' => '🎯 Quick Entry',
                'options' => [
                    ['qty' => 1, 'price' => 1, 'label' => 'Try Your Luck', 'desc' => 'Single ticket entry'],
                    ['qty' => 3, 'price' => 3, 'label' => 'Triple Chance', 'desc' => '3x better odds'],
                    ['qty' => 5, 'price' => 5, 'label' => 'Lucky Five', 'desc' => 'Popular choice'],
                ]
            ],
            
            // Value Bundles
            'value_bundles' => [
                'title' => '💰 Best Value',
                'options' => [
                    ['qty' => 10, 'price' => 10, 'label' => 'Perfect 10', 'desc' => '10x the excitement', 'badge' => 'Most Popular'],
                    ['qty' => 20, 'price' => 20, 'label' => 'Power Pack', 'desc' => 'Serious contender', 'badge' => 'Best Odds'],
                    ['qty' => 50, 'price' => 50, 'label' => 'Champion Bundle', 'desc' => 'Maximum impact', 'badge' => 'VIP Choice'],
                ]
            ],
            
            // Psychological Triggers
            'psychological' => [
                'title' => '🔥 Special Offers',
                'options' => []
            ],
            
            // Smart Recommendations
            'smart_recommendations' => [
                'title' => '🤖 Smart Picks',
                'options' => []
            ]
        ];
        
        // Add psychological triggers based on raffle status
        if ($completionRate > 0.7) {
            $strategies['psychological']['options'][] = [
                'qty' => min(25, $remaining),
                'price' => min(25, $remaining),
                'label' => 'Last Chance Rush',
                'desc' => 'Over 70% sold - Act fast!',
                'badge' => 'Limited Time',
                'urgency' => true
            ];
        }
        
        if ($completionRate < 0.3) {
            $strategies['psychological']['options'][] = [
                'qty' => 7,
                'price' => 7,
                'label' => 'Early Bird Special',
                'desc' => 'Get in early for better odds',
                'badge' => 'Early Bird',
                'early_bird' => true
            ];
        }
        
        // Add lucky number options
        $strategies['psychological']['options'][] = [
            'qty' => 8,
            'price' => 8,
            'label' => 'Lucky 8',
            'desc' => 'Lucky number in many cultures',
            'badge' => 'Lucky',
            'lucky' => true
        ];
        
        // Smart recommendations based on user behavior
        if ($userTickets > 0) {
            $strategies['smart_recommendations']['options'][] = [
                'qty' => $userTickets,
                'price' => $userTickets,
                'label' => 'Double Down',
                'desc' => "You have {$userTickets} tickets - double your chances!",
                'badge' => 'Smart Choice'
            ];
        }
        
        // Budget-based recommendations
        if ($userBudget >= 15) {
            $strategies['smart_recommendations']['options'][] = [
                'qty' => 15,
                'price' => 15,
                'label' => 'Budget Maximizer',
                'desc' => 'Perfect for your budget',
                'badge' => 'Recommended'
            ];
        }
        
        return $strategies;
    }
    
    /**
     * Get purchase motivators based on user and raffle data
     */
    public static function getPurchaseMotivators($raffle, $userTickets = 0) {
        $motivators = [];
        $remaining = $raffle['total_tickets'] - $raffle['sold_tickets'];
        $completionRate = $raffle['sold_tickets'] / $raffle['total_tickets'];
        
        // Urgency motivators
        if ($completionRate > 0.8) {
            $motivators[] = [
                'type' => 'urgency',
                'icon' => 'fa-fire',
                'color' => 'red',
                'message' => "Only {$remaining} tickets left!",
                'priority' => 'high'
            ];
        }
        
        if ($completionRate > 0.5) {
            $motivators[] = [
                'type' => 'social_proof',
                'icon' => 'fa-users',
                'color' => 'blue',
                'message' => "Over " . number_format($raffle['sold_tickets']) . " people already entered!",
                'priority' => 'medium'
            ];
        }
        
        // Value motivators
        $motivators[] = [
            'type' => 'value',
            'icon' => 'fa-trophy',
            'color' => 'gold',
            'message' => "Win RM" . number_format($raffle['prize_value']) . " for just RM1!",
            'priority' => 'high'
        ];
        
        // Odds motivators
        if ($userTickets > 0) {
            $currentOdds = round(($userTickets / $raffle['total_tickets']) * 100, 2);
            $motivators[] = [
                'type' => 'odds',
                'icon' => 'fa-percent',
                'color' => 'green',
                'message' => "Your current odds: {$currentOdds}%",
                'priority' => 'medium'
            ];
        }
        
        // Affordability motivator
        $motivators[] = [
            'type' => 'affordability',
            'icon' => 'fa-coins',
            'color' => 'yellow',
            'message' => "Just RM1 per ticket - anyone can play!",
            'priority' => 'low'
        ];
        
        return $motivators;
    }
    
    /**
     * Get gamification elements for purchase
     */
    public static function getGamificationElements($quantity) {
        $elements = [];
        
        // Milestone rewards
        $milestones = [
            5 => ['badge' => 'Starter', 'emoji' => '🌟', 'message' => 'You\'re getting serious!'],
            10 => ['badge' => 'Committed', 'emoji' => '🎯', 'message' => 'Perfect 10 achievement!'],
            20 => ['badge' => 'High Roller', 'emoji' => '🎲', 'message' => 'Big player status!'],
            50 => ['badge' => 'Champion', 'emoji' => '🏆', 'message' => 'Champion level entry!'],
            100 => ['badge' => 'Legend', 'emoji' => '👑', 'message' => 'Legendary commitment!']
        ];
        
        foreach ($milestones as $threshold => $reward) {
            if ($quantity >= $threshold) {
                $elements['milestone'] = $reward;
            }
        }
        
        // Streak bonuses
        $elements['streak_bonus'] = [
            'message' => 'Keep your winning streak alive!',
            'bonus_points' => $quantity * 0.1
        ];
        
        // Lucky number bonuses
        $luckyNumbers = [7, 8, 9, 11, 13, 21, 33, 77, 88, 99];
        if (in_array($quantity, $luckyNumbers)) {
            $elements['lucky_number'] = [
                'message' => "Lucky number {$quantity} chosen!",
                'bonus_points' => 5
            ];
        }
        
        return $elements;
    }
    
    /**
     * Get instant gratification elements
     */
    public static function getInstantGratification($quantity) {
        $gratification = [];
        
        // Instant rewards
        $gratification['loyalty_points'] = $quantity; // 1 point per RM
        $gratification['entries'] = $quantity;
        
        // Bonus calculations
        if ($quantity >= 10) {
            $gratification['bonus_entries'] = floor($quantity / 10); // 1 bonus per 10 tickets
        }
        
        if ($quantity >= 20) {
            $gratification['vip_status'] = true;
            $gratification['priority_notifications'] = true;
        }
        
        // Achievement unlocks
        $gratification['achievements'] = [];
        if ($quantity >= 5) {
            $gratification['achievements'][] = 'Serious Player';
        }
        if ($quantity >= 15) {
            $gratification['achievements'][] = 'High Roller';
        }
        if ($quantity >= 50) {
            $gratification['achievements'][] = 'Champion';
        }
        
        return $gratification;
    }
    
    /**
     * Generate purchase suggestions based on user behavior
     */
    public static function generatePurchaseSuggestions($raffle, $userHistory = [], $userBudget = 0) {
        $suggestions = [];
        
        // Analyze user's typical spending
        $avgSpend = 0;
        if (!empty($userHistory)) {
            $avgSpend = array_sum(array_column($userHistory, 'amount')) / count($userHistory);
        }
        
        // Suggest based on previous behavior
        if ($avgSpend > 0) {
            $suggestions[] = [
                'type' => 'behavioral',
                'quantity' => round($avgSpend),
                'message' => "Based on your usual spending of RM" . number_format($avgSpend, 2),
                'confidence' => 'high'
            ];
        }
        
        // Suggest based on budget
        if ($userBudget > 0) {
            $maxTickets = min($userBudget, 100); // Cap at 100 tickets
            $suggestions[] = [
                'type' => 'budget',
                'quantity' => $maxTickets,
                'message' => "Maximize your budget of RM{$userBudget}",
                'confidence' => 'medium'
            ];
        }
        
        // Suggest based on raffle popularity
        $completionRate = $raffle['sold_tickets'] / $raffle['total_tickets'];
        if ($completionRate > 0.6) {
            $suggestions[] = [
                'type' => 'popularity',
                'quantity' => 15,
                'message' => "This raffle is popular - secure your spot!",
                'confidence' => 'high'
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Get social proof elements
     */
    public static function getSocialProof($raffle) {
        $proof = [];
        
        // Recent activity
        $proof['recent_purchases'] = [
            'count' => rand(3, 15),
            'timeframe' => 'last hour',
            'message' => 'people bought tickets in the last hour'
        ];
        
        // Popular quantities
        $proof['popular_quantities'] = [
            'most_common' => [10, 5, 20],
            'message' => 'Most players buy 10 tickets'
        ];
        
        // Success stories
        $proof['success_stories'] = [
            'recent_winner' => [
                'name' => 'Sarah M.',
                'tickets' => 8,
                'prize' => 'iPhone 15 Pro',
                'message' => 'won with just 8 tickets!'
            ]
        ];
        
        return $proof;
    }
    
    /**
     * Calculate dynamic pricing incentives (even though base is RM1)
     */
    public static function getDynamicIncentives($quantity) {
        $incentives = [];
        
        // Volume bonuses
        if ($quantity >= 10) {
            $incentives['volume_bonus'] = [
                'type' => 'bonus_entries',
                'value' => floor($quantity / 10),
                'message' => 'Get ' . floor($quantity / 10) . ' bonus entries!'
            ];
        }
        
        // Loyalty multipliers
        if ($quantity >= 20) {
            $incentives['loyalty_multiplier'] = [
                'type' => 'points_multiplier',
                'multiplier' => 1.5,
                'message' => '1.5x loyalty points on this purchase!'
            ];
        }
        
        // Special occasions
        $dayOfWeek = date('N');
        if ($dayOfWeek == 5) { // Friday
            $incentives['friday_bonus'] = [
                'type' => 'weekend_bonus',
                'value' => 2,
                'message' => 'Friday bonus: +2 extra entries!'
            ];
        }
        
        return $incentives;
    }
    
    /**
     * Get optimal purchase strategy for a user and raffle
     */
    public function getOptimalStrategy($user, $raffle, $soldPercentage, $hoursRemaining) {
        $strategy = [
            'recommended_quantity' => 1,
            'strategy_type' => 'quick_entry',
            'reasons' => []
        ];
        
        // Determine strategy based on raffle status
        if ($soldPercentage >= 70) {
            $strategy['strategy_type'] = 'urgency';
            $strategy['recommended_quantity'] = 5;
            $strategy['reasons'][] = 'High demand - act fast';
        } elseif ($hoursRemaining <= 24) {
            $strategy['strategy_type'] = 'last_chance';
            $strategy['recommended_quantity'] = 3;
            $strategy['reasons'][] = 'Limited time remaining';
        } elseif ($soldPercentage < 30) {
            $strategy['strategy_type'] = 'early_bird';
            $strategy['recommended_quantity'] = 1;
            $strategy['reasons'][] = 'Early bird opportunity';
        } else {
            $strategy['strategy_type'] = 'value';
            $strategy['recommended_quantity'] = 3;
            $strategy['reasons'][] = 'Good value opportunity';
        }
        
        return $strategy;
    }
}
?> 