-- Raffle Platform Database Schema
-- Create this database in your MySQL server

CREATE DATABASE IF NOT EXISTS raffle_platform;
USE raffle_platform;

-- Raffles table
CREATE TABLE raffles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    ticket_price DECIMAL(10,2) NOT NULL,
    retail_value DECIMAL(10,2) NOT NULL,
    total_tickets INT NOT NULL,
    sold_tickets INT DEFAULT 0,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    draw_date DATETIME NOT NULL,
    winner_id INT NULL,
    category_id INT,
    brand_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id)
);

-- Enhanced Users table with loyalty system
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    loyalty_points INT DEFAULT 0,
    total_points_earned INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_checkin_date DATE NULL,
    vip_tier ENUM('bronze', 'silver', 'gold', 'diamond') DEFAULT 'bronze',
    vip_points INT DEFAULT 0,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Daily Check-in History
CREATE TABLE daily_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    checkin_date DATE NOT NULL,
    day_in_streak INT NOT NULL,
    points_awarded INT NOT NULL,
    bonus_reward TEXT NULL,
    is_weekend_bonus BOOLEAN DEFAULT FALSE,
    is_special_event BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_date (user_id, checkin_date)
);

-- Loyalty Points Transactions
CREATE TABLE loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('earned', 'spent', 'bonus', 'refund') NOT NULL,
    points_change INT NOT NULL,
    balance_after INT NOT NULL,
    source_type ENUM('checkin', 'purchase', 'referral', 'admin', 'spin', 'bonus') NOT NULL,
    source_reference VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Daily Rewards Configuration
CREATE TABLE daily_rewards_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_number INT NOT NULL,
    base_points INT NOT NULL,
    bonus_reward_type ENUM('none', 'ticket', 'multiplier', 'spin', 'discount') DEFAULT 'none',
    bonus_reward_value VARCHAR(100) NULL,
    weekend_multiplier DECIMAL(3,2) DEFAULT 1.00,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (day_number)
);

-- Lucky Spin Wheel
CREATE TABLE spin_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    reward_type ENUM('points', 'ticket', 'discount', 'cash') NOT NULL,
    reward_value VARCHAR(100) NOT NULL,
    probability DECIMAL(5,2) NOT NULL,
    min_vip_tier ENUM('bronze', 'silver', 'gold', 'diamond') DEFAULT 'bronze',
    is_active BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Spins History
CREATE TABLE user_spins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    spin_reward_id INT NOT NULL,
    reward_claimed BOOLEAN DEFAULT FALSE,
    spin_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (spin_reward_id) REFERENCES spin_rewards(id)
);

-- Loyalty Store Items
CREATE TABLE loyalty_store (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    points_cost INT NOT NULL,
    item_type ENUM('ticket_discount', 'free_ticket', 'cash_reward', 'exclusive_raffle', 'vip_upgrade') NOT NULL,
    item_value VARCHAR(255) NOT NULL,
    stock_quantity INT DEFAULT -1,
    min_vip_tier ENUM('bronze', 'silver', 'gold', 'diamond') DEFAULT 'bronze',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Purchases from Loyalty Store
CREATE TABLE loyalty_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_item_id INT NOT NULL,
    points_spent INT NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (store_item_id) REFERENCES loyalty_store(id)
);

-- Tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raffle_id INT NOT NULL,
    user_id INT NOT NULL,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'winner', 'refunded') DEFAULT 'active',
    points_discount INT DEFAULT 0,
    original_price DECIMAL(10,2) NOT NULL,
    final_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (raffle_id) REFERENCES raffles(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Winners table
CREATE TABLE winners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raffle_id INT NOT NULL,
    user_id INT NOT NULL,
    ticket_id INT NOT NULL,
    win_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed BOOLEAN DEFAULT FALSE,
    claim_date TIMESTAMP NULL,
    FOREIGN KEY (raffle_id) REFERENCES raffles(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- Platform statistics table
CREATE TABLE platform_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    total_raffles INT DEFAULT 0,
    total_winners INT DEFAULT 0,
    total_prizes_awarded DECIMAL(15,2) DEFAULT 0,
    total_points_distributed INT DEFAULT 0,
    total_checkins INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    show_brands BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Brands table
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    website_url VARCHAR(255),
    image_url VARCHAR(500),
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Raffle Tags table
CREATE TABLE raffle_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raffle_id INT NOT NULL,
    tag_name VARCHAR(100) NOT NULL,
    tag_type ENUM('category', 'brand', 'feature', 'custom') DEFAULT 'custom',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (raffle_id) REFERENCES raffles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_raffle_tag (raffle_id, tag_name)
);

-- Popular Tags table
CREATE TABLE popular_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100) NOT NULL UNIQUE,
    usage_count INT DEFAULT 1,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Brand Categories relationship table
CREATE TABLE brand_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    category_id INT NOT NULL,
    category_sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_brand_category (brand_id, category_id)
);

-- Banner Slider Management
CREATE TABLE banner_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500),
    description TEXT,
    background_image VARCHAR(500) NOT NULL,
    button_text VARCHAR(100) DEFAULT 'Get Started',
    button_url VARCHAR(500),
    badge_text VARCHAR(100),
    badge_color ENUM('yellow', 'red', 'blue', 'green', 'purple') DEFAULT 'yellow',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample categories
INSERT INTO categories (name, icon, description) VALUES
('Electronics', 'fa-mobile-alt', 'Latest gadgets and electronic devices'),
('Gaming', 'fa-gamepad', 'Gaming consoles, accessories, and games'),
('Fashion', 'fa-tshirt', 'Clothing, shoes, and fashion accessories'),
('Home & Living', 'fa-home', 'Home decor, furniture, and appliances'),
('Sports', 'fa-futbol', 'Sports equipment and outdoor gear'),
('Beauty', 'fa-spa', 'Beauty products and personal care'),
('Books', 'fa-book', 'Books, magazines, and educational materials'),
('Food & Beverages', 'fa-utensils', 'Food items, beverages, and snacks');

-- Insert sample raffles
INSERT INTO raffles (title, description, image_url, ticket_price, retail_value, total_tickets, sold_tickets, draw_date, category_id) VALUES
('iPhone 14 Pro Max', 'Latest iPhone with advanced camera system', 'images/iphone.jpg', 10.00, 5999.00, 2000, 1500, '2024-12-31 23:59:59', 1),
('MacBook Pro 16"', 'Professional laptop for creators', 'images/monitor.jpg', 25.00, 12999.00, 1000, 450, '2024-12-31 23:59:59', 1),
('PlayStation 5 Bundle', 'Next-gen gaming console with games', 'images/ps5.jpg', 15.00, 3299.00, 1000, 900, '2024-12-31 23:59:59', 2),
('AirPods Pro 2nd Gen', 'Premium wireless earbuds', 'images/airpods.jpg', 5.00, 1299.00, 1000, 300, '2024-12-31 23:59:59', 1),
('Samsung 65" 4K Smart TV', 'Ultra HD television with smart features', 'images/monitor.jpg', 20.00, 8999.00, 1000, 600, '2024-12-31 23:59:59', 1),
('Premium Gaming Chair', 'Ergonomic gaming chair with lumbar support', 'images/secretlab.jpg', 8.00, 1899.00, 1000, 850, '2024-12-31 23:59:59', 2);

-- Insert sample users with loyalty data
INSERT INTO users (email, name, phone, loyalty_points, current_streak, vip_tier) VALUES
('john@example.com', 'John Doe', '+60123456789', 1250, 5, 'silver'),
('jane@example.com', 'Jane Smith', '+60123456790', 890, 3, 'bronze'),
('mike@example.com', 'Mike Johnson', '+60123456791', 2100, 12, 'gold');

-- Insert daily rewards configuration
INSERT INTO daily_rewards_config (day_number, base_points, bonus_reward_type, bonus_reward_value, description) VALUES
(1, 50, 'none', NULL, 'Welcome back! Start your streak'),
(2, 75, 'none', NULL, 'Building momentum'),
(3, 100, 'multiplier', '1.2x', 'Third day bonus'),
(4, 150, 'none', NULL, 'Halfway to weekly bonus'),
(5, 200, 'spin', '1', 'Free spin reward'),
(6, 250, 'discount', '10%', '10% off next ticket'),
(7, 500, 'ticket', '1', 'Free ticket + Weekly milestone!'),
(8, 100, 'none', NULL, 'New week, fresh start'),
(9, 125, 'none', NULL, 'Consistency pays'),
(10, 150, 'multiplier', '1.5x', 'Double digits!'),
(11, 200, 'none', NULL, 'Going strong'),
(12, 250, 'spin', '1', 'Lucky spin time'),
(13, 300, 'discount', '15%', 'Unlucky 13? Not here!'),
(14, 600, 'ticket', '2', 'Two weeks milestone - 2 free tickets!'),
(15, 150, 'none', NULL, 'Third week begins'),
(16, 175, 'none', NULL, 'Sweet sixteen'),
(17, 200, 'multiplier', '2x', 'Double points day'),
(18, 250, 'none', NULL, 'Almost there'),
(19, 300, 'spin', '2', 'Double spin day'),
(20, 350, 'discount', '20%', 'Big discount day'),
(21, 750, 'ticket', '3', 'Three weeks - Triple ticket reward!'),
(22, 200, 'none', NULL, 'Final week starts'),
(23, 225, 'none', NULL, 'Penultimate push'),
(24, 250, 'multiplier', '2.5x', 'Almost monthly milestone'),
(25, 300, 'spin', '3', 'Triple spin spectacular'),
(26, 350, 'discount', '25%', 'Quarter off everything'),
(27, 400, 'none', NULL, 'Final stretch'),
(28, 450, 'ticket', '1', 'Bonus ticket for dedication'),
(29, 500, 'spin', '5', 'Mega spin day'),
(30, 1000, 'ticket', '5', 'MONTHLY CHAMPION! 5 free tickets + 1000 points!');

-- Insert spin wheel rewards
INSERT INTO spin_rewards (name, reward_type, reward_value, probability, image_url) VALUES
('50 Points', 'points', '50', 25.00, NULL),
('100 Points', 'points', '100', 20.00, NULL),
('200 Points', 'points', '200', 15.00, NULL),
('500 Points', 'points', '500', 10.00, NULL),
('Free Ticket', 'ticket', '1', 12.00, NULL),
('10% Discount', 'discount', '10', 10.00, NULL),
('20% Discount', 'discount', '20', 5.00, NULL),
('RM 5 Cash', 'cash', '5.00', 2.50, NULL),
('RM 10 Cash', 'cash', '10.00', 0.50, NULL);

-- Insert loyalty store items
INSERT INTO loyalty_store (name, description, points_cost, item_type, item_value, image_url) VALUES
('10% Ticket Discount', 'Get 10% off your next ticket purchase', 500, 'ticket_discount', '10', NULL),
('25% Ticket Discount', 'Get 25% off your next ticket purchase', 1200, 'ticket_discount', '25', NULL),
('Free Raffle Ticket', 'Get 1 free ticket for any active raffle', 800, 'free_ticket', '1', NULL),
('RM 5 Cash Reward', 'Instant RM 5 added to your wallet', 500, 'cash_reward', '5.00', NULL),
('RM 10 Cash Reward', 'Instant RM 10 added to your wallet', 950, 'cash_reward', '10.00', NULL),
('RM 25 Cash Reward', 'Instant RM 25 added to your wallet', 2200, 'cash_reward', '25.00', NULL),
('VIP Silver Upgrade', 'Upgrade to Silver VIP tier instantly', 3000, 'vip_upgrade', 'silver', NULL),
('Exclusive iPhone Raffle', 'Entry to VIP-only iPhone 15 raffle', 5000, 'exclusive_raffle', 'iphone_vip', NULL);

-- Insert platform statistics
INSERT INTO platform_stats (total_raffles, total_winners, total_prizes_awarded, total_points_distributed, total_checkins) VALUES
(6, 2847, 1200000.00, 125000, 8500);

-- Create indexes for better performance
CREATE INDEX idx_raffles_status ON raffles(status);
CREATE INDEX idx_raffles_draw_date ON raffles(draw_date);
CREATE INDEX idx_tickets_raffle_id ON tickets(raffle_id);
CREATE INDEX idx_tickets_user_id ON tickets(user_id);
CREATE INDEX idx_winners_raffle_id ON winners(raffle_id);
CREATE INDEX idx_users_vip_tier ON users(vip_tier);
CREATE INDEX idx_daily_checkins_user_date ON daily_checkins(user_id, checkin_date);
CREATE INDEX idx_loyalty_transactions_user ON loyalty_transactions(user_id);
CREATE INDEX idx_loyalty_transactions_type ON loyalty_transactions(transaction_type); 