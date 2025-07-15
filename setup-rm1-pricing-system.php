<?php
/**
 * RM1 Pricing System Database Setup
 * This script updates the database to support the new RM1 ticket pricing strategy
 */

require_once 'inc/database.php';

echo "Setting up RM1 Pricing System...\n";

try {
    // Update raffles table to support RM1 pricing
    $pdo->exec("
        ALTER TABLE raffles 
        ADD COLUMN IF NOT EXISTS ticket_price DECIMAL(10,2) DEFAULT 1.00,
        ADD COLUMN IF NOT EXISTS max_tickets_per_user INT DEFAULT 100,
        ADD COLUMN IF NOT EXISTS early_bird_bonus BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS lucky_numbers TEXT,
        ADD COLUMN IF NOT EXISTS social_proof_enabled BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS urgency_threshold INT DEFAULT 72
    ");
    
    // Update existing raffles to RM1 pricing
    $pdo->exec("UPDATE raffles SET ticket_price = 1.00 WHERE ticket_price != 1.00");
    
    // Create achievements table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            badge_color VARCHAR(20),
            requirement_type ENUM('tickets_purchased', 'raffles_entered', 'consecutive_days', 'total_spent') NOT NULL,
            requirement_value INT NOT NULL,
            bonus_points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create user achievements table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_id INT NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_achievement (user_id, achievement_id)
        )
    ");
    
    // Create purchase strategies table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchase_strategies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            strategy_name VARCHAR(100) NOT NULL,
            min_amount DECIMAL(10,2),
            max_amount DECIMAL(10,2),
            description TEXT,
            psychological_trigger VARCHAR(100),
            conversion_boost DECIMAL(5,2),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create user purchase analytics table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_purchase_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            raffle_id INT NOT NULL,
            strategy_used VARCHAR(100),
            tickets_purchased INT,
            amount_spent DECIMAL(10,2),
            time_to_purchase INT, -- seconds from page load to purchase
            conversion_trigger VARCHAR(100),
            session_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (raffle_id) REFERENCES raffles(id) ON DELETE CASCADE
        )
    ");
    
    // Create live activity feed table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS live_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            activity_type ENUM('purchase', 'winner', 'achievement', 'checkin') NOT NULL,
            user_name VARCHAR(100),
            raffle_title VARCHAR(200),
            tickets_count INT,
            achievement_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_visible BOOLEAN DEFAULT TRUE
        )
    ");
    
    // Insert default achievements
    $achievements = [
        ['Starter', 'Purchase your first 5 tickets', 'fas fa-star', 'bronze', 'tickets_purchased', 5, 50],
        ['Committed Player', 'Purchase 10 tickets in total', 'fas fa-trophy', 'silver', 'tickets_purchased', 10, 100],
        ['High Roller', 'Purchase 20 tickets in a single raffle', 'fas fa-gem', 'gold', 'tickets_purchased', 20, 200],
        ['Champion', 'Purchase 50 tickets in total', 'fas fa-crown', 'purple', 'tickets_purchased', 50, 500],
        ['Legend', 'Purchase 100 tickets in total', 'fas fa-medal', 'rainbow', 'tickets_purchased', 100, 1000],
        ['Raffle Explorer', 'Enter 5 different raffles', 'fas fa-compass', 'blue', 'raffles_entered', 5, 100],
        ['Dedicated Player', 'Check in for 7 consecutive days', 'fas fa-calendar-check', 'green', 'consecutive_days', 7, 300],
        ['Big Spender', 'Spend RM100 in total', 'fas fa-money-bill-wave', 'gold', 'total_spent', 100, 500]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO achievements (name, description, icon, badge_color, requirement_type, requirement_value, bonus_points)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($achievements as $achievement) {
        $stmt->execute($achievement);
    }
    
    // Insert default purchase strategies
    $strategies = [
        ['Quick Entry', 1.00, 5.00, 'Perfect for trying your luck', 'low_commitment', 15.0],
        ['Value Bundle', 10.00, 50.00, 'Better odds with bulk purchase', 'value_perception', 25.0],
        ['Power Play', 20.00, 100.00, 'Maximum chances to win', 'exclusivity', 35.0],
        ['Early Bird Special', 1.00, 20.00, 'Limited time bonus entries', 'urgency', 40.0],
        ['Lucky Numbers', 5.00, 25.00, 'Personalized lucky combinations', 'personalization', 30.0]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO purchase_strategies (strategy_name, min_amount, max_amount, description, psychological_trigger, conversion_boost)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($strategies as $strategy) {
        $stmt->execute($strategy);
    }
    
    // Update users table for enhanced tracking
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS total_tickets_purchased INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS total_amount_spent DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS raffles_entered INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS achievement_points INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS last_purchase_at TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS favorite_strategy VARCHAR(100),
        ADD COLUMN IF NOT EXISTS conversion_profile ENUM('casual', 'engaged', 'power_user') DEFAULT 'casual'
    ");
    
    // Create view for user statistics
    $pdo->exec("
        CREATE OR REPLACE VIEW user_stats AS
        SELECT 
            u.id,
            u.name,
            u.email,
            u.total_tickets_purchased,
            u.total_amount_spent,
            u.raffles_entered,
            u.achievement_points,
            u.vip_tier,
            u.conversion_profile,
            COUNT(DISTINCT ua.achievement_id) as achievements_earned,
            COALESCE(recent_activity.recent_purchases, 0) as recent_purchases
        FROM users u
        LEFT JOIN user_achievements ua ON u.id = ua.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as recent_purchases
            FROM user_purchase_analytics 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY user_id
        ) recent_activity ON u.id = recent_activity.user_id
        GROUP BY u.id
    ");
    
    // Create triggers for automatic updates
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS update_user_stats_after_purchase
        AFTER INSERT ON user_purchase_analytics
        FOR EACH ROW
        BEGIN
            UPDATE users SET 
                total_tickets_purchased = total_tickets_purchased + NEW.tickets_purchased,
                total_amount_spent = total_amount_spent + NEW.amount_spent,
                last_purchase_at = NOW()
            WHERE id = NEW.user_id;
            
            -- Update conversion profile based on spending
            UPDATE users SET 
                conversion_profile = CASE 
                    WHEN total_amount_spent >= 100 THEN 'power_user'
                    WHEN total_amount_spent >= 20 THEN 'engaged'
                    ELSE 'casual'
                END
            WHERE id = NEW.user_id;
        END
    ");
    
    // Create trigger for live activity feed
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS add_purchase_to_live_feed
        AFTER INSERT ON user_purchase_analytics
        FOR EACH ROW
        BEGIN
            INSERT INTO live_activity (activity_type, user_name, raffle_title, tickets_count)
            SELECT 'purchase', u.name, r.title, NEW.tickets_purchased
            FROM users u, raffles r
            WHERE u.id = NEW.user_id AND r.id = NEW.raffle_id;
        END
    ");
    
    echo "✅ Database schema updated successfully!\n";
    echo "✅ Achievement system created\n";
    echo "✅ Purchase strategies configured\n";
    echo "✅ Analytics tracking enabled\n";
    echo "✅ Live activity feed setup\n";
    echo "✅ User statistics view created\n";
    echo "✅ Automatic triggers configured\n";
    echo "\nRM1 Pricing System is now ready! 🎯\n";
    
} catch (PDOException $e) {
    echo "❌ Error setting up RM1 pricing system: " . $e->getMessage() . "\n";
    exit(1);
}
?> 