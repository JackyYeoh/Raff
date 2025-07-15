<?php
require_once 'inc/database.php';

echo "<h2>Setting up Raffle Tags System</h2>";

try {
    // Create raffle_tags table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS raffle_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            raffle_id INT NOT NULL,
            tag_name VARCHAR(50) NOT NULL,
            tag_type ENUM('category', 'brand', 'feature', 'custom') DEFAULT 'custom',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_raffle_tag (raffle_id, tag_name),
            FOREIGN KEY (raffle_id) REFERENCES raffles(id) ON DELETE CASCADE,
            INDEX idx_tag_name (tag_name),
            INDEX idx_tag_type (tag_type)
        )
    ");
    echo "✅ Created raffle_tags table<br>";

    // Create popular_tags table for tag analytics
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS popular_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tag_name VARCHAR(50) NOT NULL,
            usage_count INT DEFAULT 1,
            last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tag (tag_name),
            INDEX idx_usage_count (usage_count),
            INDEX idx_last_used (last_used)
        )
    ");
    echo "✅ Created popular_tags table<br>";

    // Create user_tag_preferences table for personalized recommendations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_tag_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tag_name VARCHAR(50) NOT NULL,
            preference_score DECIMAL(3,2) DEFAULT 1.00,
            interaction_count INT DEFAULT 1,
            last_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_tag (user_id, tag_name),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_preference_score (preference_score),
            INDEX idx_interaction_count (interaction_count)
        )
    ");
    echo "✅ Created user_tag_preferences table<br>";

    // Insert some default popular tags
    $defaultTags = [
        'gaming', 'electronics', 'fashion', 'home', 'sports', 'beauty', 'food', 'travel',
        'luxury', 'budget', 'trending', 'limited', 'exclusive', 'new', 'popular', 'hot',
        'gaming-console', 'smartphone', 'laptop', 'headphones', 'watch', 'shoes', 'bag',
        'cosmetics', 'perfume', 'jewelry', 'furniture', 'kitchen', 'fitness', 'outdoor'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO popular_tags (tag_name) VALUES (?)");
    foreach ($defaultTags as $tag) {
        $stmt->execute([$tag]);
    }
    echo "✅ Inserted " . count($defaultTags) . " default popular tags<br>";

    // Add tags column to raffles table if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM raffles LIKE 'tags'");
    if ($columns->rowCount() === 0) {
        $pdo->exec("ALTER TABLE raffles ADD COLUMN tags TEXT COMMENT 'Comma-separated tags for search and recommendations'");
        echo "✅ Added tags column to raffles table<br>";
    } else {
        echo "ℹ️ Tags column already exists in raffles table<br>";
    }

    echo "<br><strong>🎉 Raffle Tags System Setup Complete!</strong><br>";
    echo "<p>The tagging system is now ready. You can:</p>";
    echo "<ul>";
    echo "<li>Add tags to raffles in the admin panel</li>";
    echo "<li>Use tags for intelligent 'Just For U' recommendations</li>";
    echo "<li>Search raffles by tags</li>";
    echo "<li>Track user tag preferences for personalization</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 