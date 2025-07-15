<?php
// Setup script for tags tables
require_once 'inc/database.php';

echo "<h2>Setting up Tags Tables</h2>";

try {
    // Create raffle_tags table
    $sql = "CREATE TABLE IF NOT EXISTS raffle_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        raffle_id INT NOT NULL,
        tag_name VARCHAR(100) NOT NULL,
        tag_type ENUM('category', 'brand', 'feature', 'custom') DEFAULT 'custom',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (raffle_id) REFERENCES raffles(id) ON DELETE CASCADE,
        UNIQUE KEY unique_raffle_tag (raffle_id, tag_name)
    )";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ raffle_tags table created successfully!</p>";
    
    // Create popular_tags table
    $sql = "CREATE TABLE IF NOT EXISTS popular_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tag_name VARCHAR(100) NOT NULL UNIQUE,
        usage_count INT DEFAULT 1,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ popular_tags table created successfully!</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Tags system setup complete! You can now use tags in your raffles.</p>";
    echo "<p><a href='admin/raffles.php'>Go to Raffles Management</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error setting up tags tables: " . $e->getMessage() . "</p>";
}
?> 