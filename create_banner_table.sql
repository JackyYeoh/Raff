-- Create banner_slides table for the raffle platform
USE raffle_platform;

-- Create banner_slides table
CREATE TABLE IF NOT EXISTS banner_slides (
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

-- Insert sample banner slides
INSERT INTO banner_slides (title, subtitle, description, background_image, button_text, button_url, badge_text, badge_color, sort_order) VALUES
('Win an iPhone 15 Pro Max', 'Latest flagship smartphone', 'Get your chance to win the most advanced iPhone ever. Just RM1 per ticket!', 'images/iphone15.jpg', 'Buy Tickets Now', '#', 'FLASH DEAL', 'yellow', 1),
('PlayStation 5 Bundle', 'Gaming console + games', 'Experience next-gen gaming with PS5. Includes 3 popular games!', 'images/ps5.jpg', 'Try Your Luck', '#', 'HOT DEAL', 'red', 2),
('MacBook Air M2', 'Ultra-fast laptop', 'Powerful performance meets incredible battery life. Perfect for work and play!', 'images/monitor.jpg', 'Get Started', '#', 'NEW ARRIVAL', 'green', 3);

-- Show confirmation
SELECT 'Banner slides table created successfully!' as status;
SELECT COUNT(*) as total_banners FROM banner_slides; 